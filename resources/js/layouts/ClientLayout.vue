<template>
  <q-layout view="hHh lpR fFf">
    <q-header elevated class="bg-primary text-white">
      <q-toolbar>
        <q-toolbar-title>
          Nerdola Bank
        </q-toolbar-title>
        <q-space />
        <q-btn flat round dense icon="logout" @click="handleLogout" />
      </q-toolbar>
    </q-header>

    <q-page-container>
      <router-view />
    </q-page-container>

    <q-footer elevated class="bg-grey-8 text-white">
      <q-tabs v-model="tab" class="text-white">
        <q-route-tab
          name="dashboard"
          icon="dashboard"
          label="Dashboard"
          to="/client/dashboard"
        />
        <q-route-tab
          name="transactions"
          icon="list"
          label="Transações"
          to="/client/transactions"
        />
        <q-route-tab
          name="transfer"
          icon="send"
          label="Transferir"
          to="/client/transfer"
        />
        <q-route-tab
          name="qrcode"
          icon="qr_code"
          label="Gerar QR"
          to="/client/qrcode/generate"
        />
        <q-route-tab
          name="qrcode-read"
          icon="qr_code_scanner"
          label="Ler QR"
          to="/client/qrcode/read"
        />
      </q-tabs>
    </q-footer>
  </q-layout>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useQuasar } from 'quasar';

const router = useRouter();
const authStore = useAuthStore();
const $q = useQuasar();
const tab = ref('dashboard');

async function handleLogout() {
  $q.dialog({
    title: 'Confirmar',
    message: 'Deseja realmente sair?',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    await authStore.logout();
    router.push({ name: 'login' });
  });
}
</script>

