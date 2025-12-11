<template>
  <q-page class="flex flex-center bg-grey-2">
    <q-card class="q-pa-md" style="min-width: 350px">
      <q-card-section>
        <div class="text-h6 text-center">Nerdola Bank</div>
        <div class="text-subtitle2 text-center q-mt-sm">Faça login em sua conta</div>
      </q-card-section>

      <q-card-section>
        <q-form @submit="onSubmit" class="q-gutter-md">
          <q-input
            v-model="form.email"
            label="E-mail"
            type="email"
            :rules="[val => !!val || 'E-mail é obrigatório']"
            outlined
          />

          <q-input
            v-model="form.password"
            label="Senha"
            type="password"
            :rules="[val => !!val || 'Senha é obrigatória']"
            outlined
          />

          <div>
            <q-btn
              label="Entrar"
              type="submit"
              color="primary"
              class="full-width"
              :loading="loading"
            />
          </div>

          <div class="text-center">
            <router-link to="/auth/register" class="text-primary">
              Não tem uma conta? Cadastre-se
            </router-link>
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useQuasar } from 'quasar';

const router = useRouter();
const authStore = useAuthStore();
const $q = useQuasar();

const form = ref({
  email: '',
  password: ''
});

const loading = ref(false);

async function onSubmit() {
  loading.value = true;
  try {
    await authStore.login(form.value);
    const user = authStore.user;
    
    if (user?.role === 'admin') {
      router.push({ name: 'admin.dashboard' });
    } else {
      router.push({ name: 'client.dashboard' });
    }
    
    $q.notify({
      type: 'positive',
      message: 'Login realizado com sucesso!'
    });
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Erro ao fazer login'
    });
  } finally {
    loading.value = false;
  }
}
</script>

