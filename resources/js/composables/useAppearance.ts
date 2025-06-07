import { onMounted, ref } from 'vue';

type Appearance = 'light';

export function updateTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    // Always set light theme
    document.documentElement.classList.remove('dark');
    document.documentElement.classList.add('light');
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

export function initializeTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    // Always initialize with light theme
    updateTheme();
}

export function useAppearance() {
    const appearance = ref<Appearance>('light');

    onMounted(() => {
        // Always set to light theme
        appearance.value = 'light';
    });

    function updateAppearance(value: Appearance) {
        appearance.value = value;

        // Store in localStorage for client-side persistence
        localStorage.setItem('appearance', value);

        // Store in cookie for SSR
        setCookie('appearance', value);

        updateTheme();
    }

    return {
        appearance,
        updateAppearance,
    };
}
