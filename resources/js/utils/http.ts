import axios from 'axios'

/**
 * Instância axios central.
 *
 * - withCredentials: envia cookies de sessão Fortify para rotas web.
 * - xsrfCookieName / xsrfHeaderName: leitura automática do cookie XSRF-TOKEN
 *   para rotas web que validam CSRF.
 * - Interceptor de request: injeta Bearer token do localStorage quando
 *   disponível (rotas API /api/*). O token é lido a cada request para
 *   refletir login/logout sem precisar reinicializar a instância.
 */
const http = axios.create({
    baseURL: '/',
    withCredentials: true,
    xsrfCookieName: 'XSRF-TOKEN',
    xsrfHeaderName: 'X-XSRF-TOKEN',
})

http.interceptors.request.use((config) => {
    const token = typeof window !== 'undefined'
        ? localStorage.getItem('api_token')
        : null

    if (token) {
        config.headers = config.headers ?? {}
        config.headers['Authorization'] = `Bearer ${token}`
    }

    return config
})

export default http
