import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/auth',
    component: () => import('../layouts/AuthLayout.vue'),
    meta: { requiresGuest: true },
    children: [
      {
        path: 'login',
        name: 'login',
        component: () => import('../pages/auth/Login.vue')
      },
      {
        path: 'register',
        name: 'register',
        component: () => import('../pages/auth/Register.vue')
      }
    ]
  },
  {
    path: '/login',
    redirect: '/auth/login'
  },
  {
    path: '/register',
    redirect: '/auth/register'
  },
  {
    path: '/client',
    component: () => import('../layouts/ClientLayout.vue'),
    meta: { requiresAuth: true, requiresClient: true },
    children: [
      {
        path: 'dashboard',
        name: 'client.dashboard',
        component: () => import('../pages/client/Dashboard.vue')
      },
      {
        path: 'transactions',
        name: 'client.transactions',
        component: () => import('../pages/client/Transactions.vue')
      },
      {
        path: 'transfer',
        name: 'client.transfer',
        component: () => import('../pages/client/Transfer.vue')
      },
      {
        path: 'qrcode/generate',
        name: 'client.qrcode.form',
        component: () => import('../pages/client/QrCodeForm.vue')
      },
      {
        path: 'qrcode/read',
        name: 'client.qrcode.read',
        component: () => import('../pages/client/QrCodeReader.vue')
      }
    ]
  },
  {
    path: '/admin',
    component: () => import('../layouts/AdminLayout.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      {
        path: 'dashboard',
        name: 'admin.dashboard',
        component: () => import('../pages/admin/Dashboard.vue')
      },
      {
        path: 'users',
        name: 'admin.users',
        component: () => import('../pages/admin/Users.vue')
      },
      {
        path: 'users/:id',
        name: 'admin.users.show',
        component: () => import('../pages/admin/UserShow.vue')
      },
      {
        path: 'deposits',
        name: 'admin.deposits',
        component: () => import('../pages/admin/Deposits.vue')
      },
      {
        path: 'deposits/create',
        name: 'admin.deposits.create',
        component: () => import('../pages/admin/DepositCreate.vue')
      },
      {
        path: 'deposits/qrcode',
        name: 'admin.deposits.qrcode',
        component: () => import('../pages/admin/DepositQrCode.vue')
      },
      {
        path: 'transactions',
        name: 'admin.transactions',
        component: () => import('../pages/admin/Transactions.vue')
      }
    ]
  },
  {
    path: '/qr',
    component: () => import('../layouts/QrLayout.vue'),
    children: [
      {
        path: 'pay/:token',
        name: 'qr.pay',
        component: () => import('../pages/qr/Pay.vue')
      },
      {
        path: 'deposit/:token',
        name: 'qr.deposit',
        component: () => import('../pages/qr/Deposit.vue')
      }
    ]
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();
  
  // Se tem token mas não tem usuário, tentar carregar
  if (authStore.token && !authStore.user) {
    try {
      await authStore.fetchUser();
    } catch (error) {
      // Se falhar, limpar token e redirecionar para login
      authStore.token = null;
      authStore.user = null;
      localStorage.removeItem('token');
      next({ name: 'login' });
      return;
    }
  }
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login' });
  } else if (to.meta.requiresGuest && authStore.isAuthenticated) {
    // Redirecionar para dashboard baseado no role
    const user = authStore.user;
    if (user?.role === 'admin') {
      next({ name: 'admin.dashboard' });
    } else {
      next({ name: 'client.dashboard' });
    }
  } else if (to.meta.requiresAdmin && authStore.user?.role !== 'admin') {
    // Se não for admin, redirecionar para dashboard do cliente ou login
    if (authStore.isAuthenticated) {
      next({ name: 'client.dashboard' });
    } else {
      next({ name: 'login' });
    }
  } else if (to.meta.requiresClient && authStore.user?.role !== 'client') {
    // Se não for cliente, redirecionar para dashboard do admin ou login
    if (authStore.isAuthenticated) {
      next({ name: 'admin.dashboard' });
    } else {
      next({ name: 'login' });
    }
  } else {
    next();
  }
});

export default router;

