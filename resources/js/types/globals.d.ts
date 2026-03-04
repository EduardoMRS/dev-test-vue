import type { AppPageProps } from './index';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps, AppPageProps {}
}

declare module 'vue' {
    interface ComponentCustomProperties {
        $inertia: typeof Router;
        $page: Page;
        $headManager: ReturnType<typeof createHeadManager>;
    }
}

declare global {
    interface Notify {
        success(msg: string, options?: Record<string, unknown>): void
        error(msg: string, options?: Record<string, unknown>): void
        warning(msg: string, options?: Record<string, unknown>): void
        info(msg: string, options?: Record<string, unknown>): void
        custom(renderer: unknown): void
    }

    interface Window {
        notify?: Notify
    }
}
