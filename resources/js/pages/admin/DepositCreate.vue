<template>
  <q-page padding>
    <div class="row items-center q-mb-md">
      <q-btn flat icon="arrow_back" @click="$router.back()" />
      <div class="text-h4 q-ml-sm">Criar Depósito</div>
    </div>

    <q-card>
      <q-card-section>
        <q-form @submit="onSubmit" class="q-gutter-md">
          <q-select
            v-model="form.user_id"
            :options="users"
            option-label="name"
            option-value="id"
            label="Cliente"
            :rules="[val => !!val || 'Cliente é obrigatório']"
            outlined
            emit-value
            map-options
          />

          <q-input
            v-model.number="form.amount"
            label="Valor"
            type="number"
            step="0.01"
            min="0.01"
            :rules="[val => !!val || 'Valor é obrigatório', val => val > 0 || 'Valor deve ser maior que zero']"
            outlined
            prefix="NDL"
          />

          <q-input
            v-model="form.description"
            label="Descrição (opcional)"
            outlined
            type="textarea"
            rows="2"
          />

          <div>
            <q-btn
              label="Criar Depósito"
              type="submit"
              color="primary"
              class="full-width"
              :loading="loading"
            />
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import axios from 'axios';

const router = useRouter();
const $q = useQuasar();

const users = ref([]);
const loading = ref(false);

const form = ref({
  user_id: null,
  amount: null,
  description: ''
});

async function loadUsers() {
  try {
    const response = await axios.get('/admin/deposits/create');
    users.value = response.data.users;
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar usuários'
    });
  }
}

async function onSubmit() {
  loading.value = true;
  try {
    await axios.post('/admin/deposits', form.value);
    $q.notify({
      type: 'positive',
      message: 'Depósito criado com sucesso!'
    });
    router.push({ name: 'admin.deposits' });
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Erro ao criar depósito'
    });
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  loadUsers();
});
</script>
