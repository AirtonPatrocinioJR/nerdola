<template>
  <q-page padding>
    <div class="row items-center q-mb-md">
      <div class="text-h4">Depósitos</div>
      <q-space />
      <q-btn
        color="primary"
        label="Criar Depósito"
        icon="add"
        @click="$router.push({ name: 'admin.deposits.create' })"
      />
      <q-btn
        color="secondary"
        label="Gerar QR Code Depósito"
        icon="qr_code"
        class="q-ml-sm"
        @click="$router.push({ name: 'admin.deposits.qrcode' })"
      />
      <q-btn
        color="info"
        label="Ver QR Codes"
        icon="list"
        class="q-ml-sm"
        @click="$router.push({ name: 'admin.deposits.qrcodes' })"
      />
      <q-btn
        color="positive"
        label="Gerar QR Code Pagamento"
        icon="payment"
        class="q-ml-sm"
        @click="$router.push({ name: 'admin.payments.qrcode' })"
      />
    </div>

    <q-card>
      <q-card-section>
        <q-table
          :rows="deposits"
          :columns="columns"
          :loading="loading"
          row-key="id"
          :pagination="pagination"
          @request="onRequest"
        />
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useQuasar } from 'quasar';
import axios from 'axios';

const $q = useQuasar();

const deposits = ref([]);
const loading = ref(false);
const pagination = ref({
  page: 1,
  rowsPerPage: 20,
  rowsNumber: 0
});

const columns = [
  { name: 'id', label: 'ID', field: 'id', align: 'left' },
  { name: 'user', label: 'Cliente', field: row => row.to_user?.name || 'N/A', align: 'left' },
  { name: 'amount', label: 'Valor', field: 'amount', align: 'right', format: val => formatCurrency(val) },
  { name: 'description', label: 'Descrição', field: 'description', align: 'left' },
  { name: 'date', label: 'Data', field: 'created_at', align: 'left', format: val => formatDate(val) }
];

async function onRequest(props) {
  loading.value = true;
  pagination.value = props.pagination;
  
  try {
    const response = await axios.get('/admin/deposits', {
      params: { page: props.pagination.page }
    });
    deposits.value = response.data.data;
    pagination.value.rowsNumber = response.data.total;
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar depósitos'
    });
  } finally {
    loading.value = false;
  }
}

function formatCurrency(value) {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  }).format(value).replace('R$', 'NDL');
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

onMounted(() => {
  onRequest({ pagination: pagination.value });
});
</script>
