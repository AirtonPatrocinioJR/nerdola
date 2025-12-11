<template>
  <q-page class="flex flex-center bg-grey-2">
    <q-card class="q-pa-md" style="min-width: 350px">
      <q-card-section>
        <div class="text-h6 text-center">Nerdola Bank</div>
        <div class="text-subtitle2 text-center q-mt-sm">Criar nova conta</div>
      </q-card-section>

      <q-card-section>
        <q-form @submit="onSubmit" class="q-gutter-md">
          <q-input
            v-model="form.name"
            label="Nome"
            :rules="[val => !!val || 'Nome é obrigatório']"
            outlined
          />

          <q-input
            v-model="form.email"
            label="E-mail"
            type="email"
            :rules="[val => !!val || 'E-mail é obrigatório']"
            outlined
          />

          <q-input
            v-model="form.phone"
            label="Telefone"
            mask="(##) #####-####"
            :rules="[val => !!val || 'Telefone é obrigatório']"
            outlined
          />

          <q-input
            v-model="form.password"
            label="Senha"
            type="password"
            :rules="[val => !!val || 'Senha é obrigatória', val => val.length >= 8 || 'Senha deve ter no mínimo 8 caracteres']"
            outlined
          />

          <q-input
            v-model="form.password_confirmation"
            label="Confirmar Senha"
            type="password"
            :rules="[val => !!val || 'Confirmação é obrigatória', val => val === form.password || 'Senhas não coincidem']"
            outlined
          />

          <div>
            <q-btn
              label="Cadastrar"
              type="submit"
              color="primary"
              class="full-width"
              :loading="loading"
            />
          </div>

          <div class="text-center">
            <router-link to="/auth/login" class="text-primary">
              Já tem uma conta? Faça login
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
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: ''
});

const loading = ref(false);

async function onSubmit() {
  loading.value = true;
  try {
    await authStore.register(form.value);
    router.push({ name: 'client.dashboard' });
    $q.notify({
      type: 'positive',
      message: 'Conta criada com sucesso!'
    });
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Erro ao criar conta'
    });
  } finally {
    loading.value = false;
  }
}
</script>

