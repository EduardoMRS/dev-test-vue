import { ref, computed, onMounted } from 'vue'
import axios from '@/utils/http'

function urlBase64ToUint8Array(base64String: string): Uint8Array<ArrayBuffer> {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
    const rawData = atob(base64)
    const outputArray = new Uint8Array(rawData.length) as Uint8Array<ArrayBuffer>
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i)
    }
    return outputArray
}

async function getVapidKey(): Promise<string | null> {
    try {
        const res = await axios.get('/api/notifications/vapid-key')
        return res.data?.data?.public_key ?? null
    } catch {
        return null
    }
}

async function getRegistration(): Promise<ServiceWorkerRegistration | null> {
    if (!('serviceWorker' in navigator)) return null
    return navigator.serviceWorker.getRegistration().then(reg => reg ?? null)
}

export function usePush() {
    const subscribed = ref(false)
    const loading = ref(false)
    const registration = ref<ServiceWorkerRegistration | null>(null)

    async function ensureRegistration(): Promise<ServiceWorkerRegistration | null> {
        if (registration.value) return registration.value
        if (!('serviceWorker' in navigator)) return null

        // Tenta obter registro existente antes de criar um novo
        const existing = await navigator.serviceWorker.getRegistration('/sw.js')
        if (existing) {
            registration.value = existing
            return existing
        }

        const reg = await navigator.serviceWorker.register('/sw.js', { scope: '/' })
        // Aguarda o SW estar ativo antes de usar o pushManager
        await navigator.serviceWorker.ready
        registration.value = reg
        return reg
    }

    async function checkSubscription(): Promise<boolean> {
        const reg = await getRegistration()
        if (!reg) return false
        registration.value = reg
        const sub = await reg.pushManager.getSubscription()
        return !!sub
    }

    async function subscribe(): Promise<void> {
        const reg = await ensureRegistration()
        if (!reg) throw new Error('Service Worker não disponível')

        const publicKey = await getVapidKey()
        if (!publicKey) throw new Error('VAPID key não disponível')

        const sub = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(publicKey) as BufferSource,
        })

        const rawKey = sub.getKey?.('p256dh') ?? null
        const rawAuth = sub.getKey?.('auth') ?? null

        const body = {
            endpoint: sub.endpoint,
            keys: {
                p256dh: rawKey ? btoa(String.fromCharCode(...new Uint8Array(rawKey))) : '',
                auth: rawAuth ? btoa(String.fromCharCode(...new Uint8Array(rawAuth))) : '',
            },
        }

        try {
            await axios.post('/api/notifications/subscribe', body)
        } catch (e) {
            await sub.unsubscribe().catch(() => {})
            throw e
        }
    }

    async function unsubscribe(): Promise<void> {
        const reg = registration.value ?? (await getRegistration())
        if (!reg) return
        const sub = await reg.pushManager.getSubscription()
        if (!sub) return
        await axios.post('/api/notifications/unsubscribe', { endpoint: sub.endpoint })
        await sub.unsubscribe().catch(() => {})
    }

    async function toggle(): Promise<void> {
        if (typeof Notification === 'undefined') {
            window.notify?.warning('Notificações não são suportadas neste navegador')
            return
        }

        if (Notification.permission === 'default') {
            const perm = await Notification.requestPermission()
            if (perm !== 'granted') {
                window.notify?.warning('Permissão de notificação negada')
                return
            }
        }

        if (Notification.permission === 'denied') {
            window.notify?.error('Notificações bloqueadas. Altere nas configurações do navegador.')
            return
        }

        loading.value = true
        try {
            if (!subscribed.value) {
                await subscribe()
                subscribed.value = true
                window.notify?.success('Notificações ativadas')
            } else {
                await unsubscribe()
                subscribed.value = false
                window.notify?.info('Notificações desativadas')
            }
        } catch (e) {
            window.notify?.error('Erro ao gerenciar notificações')
            console.error('[usePush]', e)
        } finally {
            loading.value = false
        }
    }

    onMounted(async () => {
        try {
            subscribed.value = await checkSubscription()
        } catch {
            subscribed.value = false
        }
    })

    const label = computed(() => (subscribed.value ? 'Desativar notificações' : 'Ativar notificações'))
    const buttonClass = computed(() =>
        subscribed.value ? 'bg-red-600 text-white' : 'bg-green-600 text-white',
    )

    return { subscribed, loading, toggle, label, buttonClass }
}
