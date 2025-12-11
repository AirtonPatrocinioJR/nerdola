<template>
  <q-page padding>
    <div class="row items-center q-mb-md">
      <div class="text-h4">QR Codes de Depósito</div>
      <q-space />
      <q-btn
        color="primary"
        label="Gerar Novo QR Code"
        icon="add"
        @click="$router.push({ name: 'admin.deposits.qrcode' })"
      />
    </div>

    <!-- Filtros -->
    <q-card class="q-mb-md">
      <q-card-section>
        <div class="row q-gutter-md">
          <div class="col-12 col-md-3">
            <q-select
              v-model="filters.is_active"
              :options="statusOptions"
              option-label="label"
              option-value="value"
              label="Status"
              clearable
              outlined
              dense
              emit-value
              map-options
              @update:model-value="loadQrCodes"
            />
          </div>
          <div class="col-12 col-md-3">
            <q-btn
              color="primary"
              label="Filtrar"
              class="full-width"
              @click="loadQrCodes"
            />
          </div>
          <div class="col-12 col-md-3">
            <q-btn
              flat
              label="Limpar Filtros"
              class="full-width"
              @click="clearFilters"
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Lista de QR Codes -->
    <q-card>
      <q-card-section>
        <q-table
          :rows="qrCodes"
          :columns="columns"
          :loading="loading"
          row-key="id"
          :pagination="pagination"
          @request="onRequest"
        >
          <template v-slot:body-cell-amount="props">
            <q-td :props="props">
              {{ props.value ? formatCurrency(props.value) : 'Variável' }}
            </q-td>
          </template>

          <template v-slot:body-cell-status="props">
            <q-td :props="props">
              <q-chip
                :color="props.value ? 'positive' : 'negative'"
                text-color="white"
                size="sm"
              >
                {{ props.value ? 'Ativo' : 'Inativo' }}
              </q-chip>
            </q-td>
          </template>

          <template v-slot:body-cell-expires_at="props">
            <q-td :props="props">
              {{ props.value ? formatDate(props.value) : 'Sem expiração' }}
            </q-td>
          </template>

          <template v-slot:body-cell-usage="props">
            <q-td :props="props">
              {{ props.row.usage_limit === null ? 'Ilimitado' : `${props.row.times_used} / ${props.row.usage_limit}` }}
            </q-td>
          </template>

          <template v-slot:body-cell-actions="props">
            <q-td :props="props">
              <q-btn
                flat
                round
                dense
                icon="visibility"
                color="primary"
                @click="viewQrCode(props.row)"
              />
              <q-btn
                flat
                round
                dense
                icon="content_copy"
                color="secondary"
                @click="copyQrCodeUrl(props.row)"
              />
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <!-- Dialog para visualizar QR Code -->
    <q-dialog v-model="showQrCodeDialog" persistent>
      <q-card style="min-width: 300px">
        <q-card-section>
          <div class="text-h6">QR Code de Depósito</div>
        </q-card-section>

        <q-card-section class="q-pt-none text-center" v-if="selectedQrCode">
          <div class="q-mb-md">
            <div class="text-caption">
              <strong>Valor:</strong> {{ selectedQrCode.amount ? formatCurrency(selectedQrCode.amount) : 'Variável' }}
            </div>
            <div v-if="selectedQrCode.description" class="text-caption">
              <strong>Descrição:</strong> {{ selectedQrCode.description }}
            </div>
            <div class="text-caption">
              <strong>Status:</strong> {{ selectedQrCode.is_active ? 'Ativo' : 'Inativo' }}
            </div>
            <div v-if="selectedQrCode.expires_at" class="text-caption">
              <strong>Expira em:</strong> {{ formatDate(selectedQrCode.expires_at) }}
            </div>
            <div class="text-caption">
              <strong>Uso:</strong> {{ selectedQrCode.usage_limit === null ? 'Ilimitado' : `${selectedQrCode.times_used} / ${selectedQrCode.usage_limit}` }}
            </div>
          </div>
          
          <div v-if="selectedQrCodeUrl" class="q-mb-md">
            <img
              :src="generateQrCodeImage(selectedQrCodeUrl)"
              alt="QR Code"
              style="max-width: 100%; height: auto;"
            />
          </div>
          
          <div class="q-mt-md text-caption text-grey-7">
            {{ selectedQrCodeUrl }}
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Fechar" color="primary" @click="showQrCodeDialog = false" />
          <q-btn flat label="Copiar Link" color="primary" @click="copyUrl" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useQuasar } from 'quasar';
import axios from 'axios';

const $q = useQuasar();

const qrCodes = ref([]);
const loading = ref(false);
const showQrCodeDialog = ref(false);
const selectedQrCode = ref(null);
const selectedQrCodeUrl = ref('');

const pagination = ref({
  page: 1,
  rowsPerPage: 20,
  rowsNumber: 0
});

const filters = ref({
  is_active: null
});

const statusOptions = [
  { label: 'Ativo', value: true },
  { label: 'Inativo', value: false }
];

const columns = [
  { name: 'id', label: 'ID', field: 'id', align: 'left' },
  { name: 'amount', label: 'Valor', field: 'amount', align: 'right' },
  { name: 'description', label: 'Descrição', field: 'description', align: 'left' },
  { name: 'status', label: 'Status', field: 'is_active', align: 'center' },
  { name: 'expires_at', label: 'Expira em', field: 'expires_at', align: 'left' },
  { name: 'usage', label: 'Uso', field: 'times_used', align: 'center' },
  { name: 'created_at', label: 'Criado em', field: 'created_at', align: 'left', format: val => formatDate(val) },
  { name: 'actions', label: 'Ações', align: 'center' }
];

async function onRequest(props) {
  loading.value = true;
  pagination.value = props.pagination;
  
  try {
    const params = {
      page: props.pagination.page,
      per_page: props.pagination.rowsPerPage || 20
    };
    
    if (filters.value.is_active !== null) {
      params.is_active = filters.value.is_active;
    }

    const response = await axios.get('/admin/deposits/qrcodes', { params });
    
    if (response.data && response.data.data) {
      qrCodes.value = response.data.data;
      pagination.value.rowsNumber = response.data.total || 0;
      pagination.value.page = response.data.current_page || 1;
    } else if (Array.isArray(response.data)) {
      qrCodes.value = response.data;
      pagination.value.rowsNumber = response.data.length;
    } else {
      qrCodes.value = [];
      pagination.value.rowsNumber = 0;
    }
  } catch (error) {
    console.error('Erro ao carregar QR Codes:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Erro ao carregar QR Codes de depósito'
    });
    qrCodes.value = [];
    pagination.value.rowsNumber = 0;
  } finally {
    loading.value = false;
  }
}

function loadQrCodes() {
  pagination.value.page = 1;
  onRequest({ pagination: pagination.value });
}

function clearFilters() {
  filters.value = {
    is_active: null
  };
  pagination.value.page = 1;
  loadQrCodes();
}

function viewQrCode(qrCode) {
  selectedQrCode.value = qrCode;
  selectedQrCodeUrl.value = getQrCodeUrl(qrCode);
  showQrCodeDialog.value = true;
}

function getQrCodeUrl(qrCode) {
  const baseUrl = window.location.origin;
  return `${baseUrl}/qr/deposit/${qrCode.token}`;
}

function generateQrCodeImage(url) {
  // Usar uma biblioteca de QR Code no frontend ou gerar via API
  // Por enquanto, vamos usar uma API externa simples
  return `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(url)}`;
}

function copyQrCodeUrl(qrCode) {
  const url = getQrCodeUrl(qrCode);
  navigator.clipboard.writeText(url).then(() => {
    $q.notify({
      type: 'positive',
      message: 'Link copiado para a área de transferência!'
    });
  });
}

function copyUrl() {
  if (selectedQrCodeUrl.value) {
    navigator.clipboard.writeText(selectedQrCodeUrl.value).then(() => {
      $q.notify({
        type: 'positive',
        message: 'Link copiado para a área de transferência!'
      });
    });
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

