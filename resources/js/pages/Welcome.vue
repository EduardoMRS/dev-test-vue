<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { dashboard, login, register } from '@/routes';
import { CheckCircle2, Bell, Mail, Shield, ListTodo } from 'lucide-vue-next';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';

const { t } = useI18n();

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const demoUsers = [
    { label: t('landing.demo.user1'), email: 'joao@teste.com', password: '12345678' },
    { label: t('landing.demo.user2'), email: 'maria@teste.com', password: '12345678' },
];

const features = [
    { icon: ListTodo, text: t('landing.features.tasks') },
    { icon: Bell, text: t('landing.features.reminders') },
    { icon: Mail, text: t('landing.features.notifications') },
    { icon: Shield, text: t('landing.features.secure') },
];
</script>

<template>
    <Head :title="t('app.welcome')">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <!-- Header -->
        <header class="border-b border-border/40">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-2">
                    <CheckCircle2 class="size-6 text-primary" />
                    <span class="text-lg font-semibold">{{ t('app.name') }}</span>
                </div>
                <nav class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        as="button"
                    >
                        <Button variant="outline" size="sm">Dashboard</Button>
                    </Link>
                    <template v-else>
                        <Link :href="login()">
                            <Button variant="ghost" size="sm">{{ t('app.login') }}</Button>
                        </Link>
                        <Link v-if="canRegister" :href="register()">
                            <Button size="sm">{{ t('app.register') }}</Button>
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <main class="mx-auto flex max-w-5xl flex-1 flex-col items-center justify-center px-6 py-16">
            <div class="text-center">
                <div class="mx-auto mb-6 flex size-16 items-center justify-center rounded-2xl bg-primary/10">
                    <CheckCircle2 class="size-8 text-primary" />
                </div>
                <h1 class="mb-4 text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                    {{ t('landing.title') }}
                </h1>
                <p class="mx-auto max-w-2xl text-lg text-muted-foreground">
                    {{ t('landing.subtitle') }}
                </p>
            </div>

            <!-- Features -->
            <div class="mt-16 grid w-full max-w-3xl gap-4 sm:grid-cols-2">
                <div
                    v-for="(feature, i) in features"
                    :key="i"
                    class="flex items-start gap-3 rounded-lg border border-border/50 bg-card p-4"
                >
                    <component :is="feature.icon" class="mt-0.5 size-5 shrink-0 text-primary" />
                    <span class="text-sm">{{ feature.text }}</span>
                </div>
            </div>

            <!-- Demo credentials -->
            <section class="mt-16 w-full max-w-3xl">
                <Card>
                    <CardHeader class="text-center">
                        <CardTitle>{{ t('landing.demo.title') }}</CardTitle>
                        <CardDescription>{{ t('landing.demo.description') }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div
                                v-for="user in demoUsers"
                                :key="user.email"
                                class="rounded-lg border border-border/50 bg-muted/30 p-4"
                            >
                                <h4 class="mb-2 font-medium">{{ user.label }}</h4>
                                <div class="space-y-1 text-sm">
                                    <p>
                                        <span class="text-muted-foreground">{{ t('landing.demo.email') }}:</span>
                                        <code class="ml-1 rounded bg-muted px-1.5 py-0.5 font-mono text-xs">{{ user.email }}</code>
                                    </p>
                                    <p>
                                        <span class="text-muted-foreground">{{ t('landing.demo.password') }}:</span>
                                        <code class="ml-1 rounded bg-muted px-1.5 py-0.5 font-mono text-xs">{{ user.password }}</code>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-col items-center gap-3">
                            <Link :href="login()">
                                <Button size="lg">{{ t('app.login') }}</Button>
                            </Link>
                            <span v-if="canRegister" class="text-sm text-muted-foreground">
                                {{ t('landing.demo.or') }}
                                <Link :href="register()" class="font-medium text-primary underline underline-offset-4 hover:text-primary/80">
                                    {{ t('landing.demo.createAccount') }}
                                </Link>
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t border-border/40 py-6 text-center text-xs text-muted-foreground">
            <p>Laravel + Vue.js + Inertia.js &mdash; {{ t('app.name') }}</p>
        </footer>
    </div>
</template>
