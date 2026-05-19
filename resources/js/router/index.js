import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
    // Public
    {
        path: '/',
        component: () => import('@/layouts/PublicLayout.vue'),
        children: [
            { path: '', name: 'home', component: () => import('@/pages/public/HomePage.vue') },
            { path: 'about', name: 'about', component: () => import('@/pages/public/AboutPage.vue') },
            { path: 'privacy', name: 'privacy', component: () => import('@/pages/public/PrivacyPage.vue') },
            { path: 'terms', name: 'terms', component: () => import('@/pages/public/TermsPage.vue') },
            { path: 'cookies', name: 'cookies', component: () => import('@/pages/public/CookiePolicyPage.vue') },
        ],
    },

    // Auth
    {
        path: '/',
        component: () => import('@/layouts/GuestLayout.vue'),
        meta: { requiresGuest: true },
        children: [
            { path: 'login', name: 'login', component: () => import('@/pages/auth/LoginPage.vue') },
            { path: 'try', name: 'guest', component: () => import('@/pages/auth/GuestPage.vue') },
            { path: 'register', name: 'register', meta: { requiresGuestUpgrade: true }, component: () => import('@/pages/auth/RegisterPage.vue') },
        ],
    },

    // User dashboard
    {
        path: '/dashboard',
        component: () => import('@/layouts/UserLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            { path: '', name: 'user.dashboard', component: () => import('@/pages/user/DashboardPage.vue') },
            { path: 'profile', name: 'user.profile', component: () => import('@/pages/user/ProfilePage.vue') },
            { path: 'characters', name: 'user.characters', component: () => import('@/pages/user/CharactersPage.vue') },
            { path: 'chat/:characterId', name: 'user.chat', component: () => import('@/pages/user/ChatPage.vue') },
        ],
    },

    // Admin dashboard
    {
        path: '/admin',
        component: () => import('@/layouts/AdminLayout.vue'),
        meta: { requiresAuth: true, requiresAdmin: true },
        children: [
            { path: '', name: 'admin.dashboard', component: () => import('@/pages/admin/DashboardPage.vue') },
            { path: 'users', name: 'admin.users', component: () => import('@/pages/admin/UsersPage.vue') },
            { path: 'characters', name: 'admin.characters', component: () => import('@/pages/admin/CharactersPage.vue') },
        ],
    },

    // Fallback
    { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach(async (to) => {
    const auth = useAuthStore()

    await auth.fetchUser()

    // /register is upgrade-only: you must have a guest session first.
    if (to.meta.requiresGuestUpgrade) {
        if (!auth.isAuthenticated) return { name: 'guest' }            // haven't tried yet
        if (!auth.isGuest) {                                           // already a full account
            return auth.isAdmin ? { name: 'admin.dashboard' } : { name: 'user.dashboard' }
        }
        return // authenticated guest → allow the upgrade page
    }

    // Guests (no-account sessions) may still reach /register to upgrade in place.
    if (to.meta.requiresGuest && auth.isAuthenticated && !auth.isGuest) {
        return auth.isAdmin ? { name: 'admin.dashboard' } : { name: 'user.dashboard' }
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { name: 'login' }
    }

    if (to.meta.requiresAdmin && !auth.isAdmin) {
        return { name: 'user.dashboard' }
    }
})

// After a deploy, a still-open SPA may try to lazy-load a chunk filename that
// no longer exists. Reload once to pull the fresh build instead of dead-ending.
router.onError((error, to) => {
    const msg = error?.message || ''
    const isChunkError =
        /Failed to fetch dynamically imported module/i.test(msg) ||
        /Importing a module script failed/i.test(msg) ||
        /error loading dynamically imported module/i.test(msg)

    if (isChunkError && !sessionStorage.getItem('chunk-reloaded')) {
        sessionStorage.setItem('chunk-reloaded', '1')
        window.location.assign(to?.fullPath || window.location.pathname)
    }
})

// Clear the one-shot guard once a navigation completes successfully.
router.afterEach(() => sessionStorage.removeItem('chunk-reloaded'))

export default router
