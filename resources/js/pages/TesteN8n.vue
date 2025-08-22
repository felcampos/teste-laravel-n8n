<template>
    <div class="min-h-screen bg-gray-50 py-8">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold text-gray-900 mb-2">
            🔗 Teste Integração Laravel + n8n
          </h1>
          <p class="text-gray-600">
            VILT Stack (Vue + Inertia + Laravel + Tailwind)
          </p>
        </div>
  
        <!-- Configuração -->
        <div class="bg-white rounded-lg shadow mb-6 p-6">
          <h2 class="text-lg font-semibold mb-4">⚙️ Configuração</h2>
          
          <!-- Info do ambiente atual -->
          <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-4">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <span class="text-2xl">🌍</span>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">
                  Ambiente Atual: {{ configuracao.ambiente.toUpperCase() }}
                </h3>
                <p class="text-sm text-blue-700">
                  Webhook ativo: {{ configuracao.is_producao ? 'Produção' : 'Teste' }}
                </p>
                <code class="text-xs text-blue-600">{{ configuracao.webhook_url }}</code>
              </div>
            </div>
          </div>
  
          <div class="grid grid-cols-1 gap-4">
            <!-- Seletor de ambiente -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Forçar Ambiente (opcional)
              </label>
              <select
                v-model="form.forcarAmbiente"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">Usar ambiente atual ({{ configuracao.ambiente }})</option>
                <option value="teste">Forçar Teste</option>
                <option value="producao">Forçar Produção</option>
              </select>
            </div>
  
            <!-- URL personalizada -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Webhook URL Personalizada (opcional)
              </label>
              <input
                v-model="form.webhookUrl"
                type="url"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                :placeholder="configuracao.webhook_url"
              >
              <p class="mt-1 text-xs text-gray-500">
                Deixe vazio para usar o webhook do ambiente atual
              </p>
            </div>
          </div>
        </div>
  
        <!-- Testes Disponíveis -->
        <div class="bg-white rounded-lg shadow mb-6 p-6">
          <h2 class="text-lg font-semibold mb-4">🧪 Testes Disponíveis</h2>
          
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Teste Conectividade -->
            <div class="border border-gray-200 rounded-lg p-4">
              <h3 class="font-medium mb-2">🔌 Conectividade</h3>
              <p class="text-sm text-gray-600 mb-4">
                Testa ambos webhooks e health do n8n
              </p>
              <button
                @click="executarTeste('conectividade')"
                :disabled="carregando"
                class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50"
              >
                {{ carregando === 'conectividade' ? 'Testando...' : 'Testar' }}
              </button>
            </div>
  
            <!-- Teste Simples -->
            <div class="border border-gray-200 rounded-lg p-4">
              <h3 class="font-medium mb-2">📤 Teste Simples</h3>
              <p class="text-sm text-gray-600 mb-4">
                Envia dados básicos para testar conectividade
              </p>
              <button
                @click="executarTeste('simples')"
                :disabled="carregando"
                class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50"
              >
                {{ carregando === 'simples' ? 'Enviando...' : 'Executar' }}
              </button>
            </div>
  
            <!-- Teste Usuários -->
            <div class="border border-gray-200 rounded-lg p-4">
              <h3 class="font-medium mb-2">👥 Teste Usuários</h3>
              <p class="text-sm text-gray-600 mb-4">
                Envia lista de usuários para o n8n
              </p>
              <button
                @click="executarTeste('usuarios')"
                :disabled="carregando"
                class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 disabled:opacity-50"
              >
                {{ carregando === 'usuarios' ? 'Enviando...' : 'Executar' }}
              </button>
            </div>
  
            <!-- Teste Personalizado -->
            <div class="border border-gray-200 rounded-lg p-4">
              <h3 class="font-medium mb-2">⚡ Teste Personalizado</h3>
              <p class="text-sm text-gray-600 mb-4">
                Envia dados customizados
              </p>
              <button
                @click="mostrarFormPersonalizado = !mostrarFormPersonalizado"
                class="w-full bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700"
              >
                {{ mostrarFormPersonalizado ? 'Fechar' : 'Configurar' }}
              </button>
            </div>
          </div>
  
          <!-- Form Personalizado -->
          <div v-if="mostrarFormPersonalizado" class="mt-6 border-t pt-6">
            <h3 class="font-medium mb-4">Dados Personalizados (JSON)</h3>
            <textarea
              v-model="form.dadosPersonalizados"
              rows="6"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 font-mono text-sm"
              placeholder='{"chave": "valor", "numero": 123, "array": [1, 2, 3]}'
            ></textarea>
            <button
              @click="executarTestePersonalizado"
              :disabled="carregando"
              class="mt-3 bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 disabled:opacity-50"
            >
              {{ carregando === 'personalizado' ? 'Enviando...' : 'Enviar Dados Personalizados' }}
            </button>
          </div>
        </div>
  
        <!-- Resultados -->
        <div v-if="resultado" class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center mb-4">
            <h2 class="text-lg font-semibold">📊 Resultado do Teste</h2>
            <div class="ml-auto">
              <span 
                :class="[
                  'px-3 py-1 rounded-full text-sm font-medium',
                  resultado.status === 'sucesso' || resultado.status === 'concluído' 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-red-100 text-red-800'
                ]"
              >
                {{ resultado.status }}
              </span>
            </div>
          </div>
  
          <!-- Resultado em formato legível -->
          <div class="space-y-4">
            <!-- Info do ambiente usado -->
            <div v-if="resultado.ambiente_webhook" class="bg-gray-50 p-3 rounded-md">
              <p class="text-sm">
                <strong>Ambiente usado:</strong> {{ resultado.ambiente_webhook }}
              </p>
              <p class="text-xs text-gray-600 mt-1">{{ resultado.webhook_url }}</p>
            </div>
  
            <!-- Testes de conectividade -->
            <div v-if="resultado.testes_webhooks">
              <h4 class="font-medium text-gray-700">Testes de Conectividade:</h4>
              <div class="space-y-3">
                <div 
                  v-for="(teste, ambiente) in resultado.testes_webhooks" 
                  :key="ambiente"
                  class="border border-gray-200 rounded-md p-3"
                >
                  <div class="flex items-center justify-between mb-2">
                    <span class="font-medium capitalize">{{ ambiente }}</span>
                    <span 
                      :class="[
                        'px-2 py-1 rounded text-xs',
                        teste.sucesso ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                      ]"
                    >
                      {{ teste.sucesso ? 'Online' : 'Offline' }}
                    </span>
                  </div>
                  <p class="text-xs text-gray-600 mb-1">{{ teste.url }}</p>
                  <div v-if="teste.tempo_resposta" class="text-xs text-gray-500">
                    Tempo: {{ Math.round(teste.tempo_resposta * 1000) }}ms
                  </div>
                  <div v-if="teste.erro" class="text-xs text-red-600">
                    Erro: {{ teste.erro }}
                  </div>
                </div>
              </div>
              
              <div v-if="resultado.health_n8n" class="mt-3 p-3 border border-gray-200 rounded-md">
                <div class="flex items-center justify-between">
                  <span class="font-medium">Health n8n</span>
                  <span 
                    :class="[
                      'px-2 py-1 rounded text-xs',
                      resultado.health_n8n.sucesso ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                    ]"
                  >
                    {{ resultado.health_n8n.sucesso ? 'Saudável' : 'Problema' }}
                  </span>
                </div>
              </div>
            </div>
  
            <!-- Dados enviados -->
            <div v-if="resultado.dados_enviados">
              <h4 class="font-medium text-gray-700">Dados Enviados:</h4>
              <pre class="bg-gray-50 p-3 rounded-md text-sm overflow-x-auto">{{ JSON.stringify(resultado.dados_enviados, null, 2) }}</pre>
            </div>
  
            <!-- Resposta do n8n -->
            <div v-if="resultado.resposta_n8n">
              <h4 class="font-medium text-gray-700">Resposta do n8n:</h4>
              <pre class="bg-blue-50 p-3 rounded-md text-sm overflow-x-auto">{{ JSON.stringify(resultado.resposta_n8n, null, 2) }}</pre>
            </div>
  
            <!-- Resultados por usuário -->
            <div v-if="resultado.resultados">
              <h4 class="font-medium text-gray-700">Resultados por Usuário:</h4>
              <div class="space-y-2">
                <div 
                  v-for="(item, index) in resultado.resultados" 
                  :key="index"
                  class="border border-gray-200 rounded-md p-3"
                >
                  <div class="flex items-center justify-between mb-2">
                    <span class="font-medium">{{ item.usuario.nome }}</span>
                    <span 
                      :class="[
                        'px-2 py-1 rounded text-xs',
                        item.sucesso ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                      ]"
                    >
                      {{ item.sucesso ? 'Sucesso' : 'Erro' }}
                    </span>
                  </div>
                  <div v-if="item.tempo_resposta" class="text-xs text-gray-500 mb-1">
                    Tempo: {{ Math.round(item.tempo_resposta * 1000) }}ms
                  </div>
                  <pre class="bg-gray-50 p-2 rounded text-xs overflow-x-auto">{{ JSON.stringify(item.resposta, null, 2) }}</pre>
                </div>
              </div>
            </div>
  
            <!-- Informações adicionais -->
            <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
              <div v-if="resultado.status_code">
                <strong>Status Code:</strong> {{ resultado.status_code }}
              </div>
              <div v-if="resultado.tempo_resposta">
                <strong>Tempo de Resposta:</strong> {{ Math.round(resultado.tempo_resposta * 1000) }}ms
              </div>
              <div v-if="resultado.tempo_total">
                <strong>Tempo Total:</strong> {{ Math.round(resultado.tempo_total * 1000) }}ms
              </div>
              <div v-if="resultado.total_usuarios">
                <strong>Total de Usuários:</strong> {{ resultado.total_usuarios }}
              </div>
            </div>
          </div>
  
          <!-- Raw JSON (para debug) -->
          <details class="mt-4">
            <summary class="cursor-pointer text-sm text-gray-600 hover:text-gray-800">
              Ver JSON completo (debug)
            </summary>
            <pre class="mt-2 bg-gray-900 text-green-400 p-4 rounded-md text-xs overflow-x-auto">{{ JSON.stringify(resultado, null, 2) }}</pre>
          </details>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, reactive, watch, onMounted } from 'vue'
  import { router } from '@inertiajs/vue3'
  
  // Props
  const props = defineProps({
    webhookUrl: String,
    ambiente: String,
    configuracao: Object,
    resultadoTeste: Object,  // Resultado do teste via session
    tipoTesteExecutado: String // Tipo do teste executado
  })
  
  // Estado reativo
  const carregando = ref(false)
  const resultado = ref(null)
  const mostrarFormPersonalizado = ref(false)
  
  // Observar mudanças no resultado do teste (vem do backend)
  watch(() => props.resultadoTeste, (novoResultado) => {
    if (novoResultado) {
      resultado.value = novoResultado
      carregando.value = false
    }
  }, { immediate: true })
  
  const form = reactive({
    webhookUrl: '',
    forcarAmbiente: '',
    dadosPersonalizados: JSON.stringify({
      exemplo: 'dados personalizados',
      numero: 42,
      array: ['item1', 'item2', 'item3'],
      objeto: {
        chave: 'valor'
      }
    }, null, 2)
  })
  
  // Métodos
  function executarTeste(tipo) {
    carregando.value = tipo
    resultado.value = null
    
    const dados = {
      tipo: tipo,
      webhook_url: form.webhookUrl || null,
      forcar_ambiente: form.forcarAmbiente || null
    }
    
    // Usando router do Inertia corretamente
    router.post('/teste-n8n/executar', dados, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: (page) => {
        // Para response JSON, o Inertia precisa ser tratado diferente
        // Vamos modificar o controller para retornar via session
      },
      onError: (errors) => {
        resultado.value = {
          status: 'erro',
          mensagem: 'Erro na requisição',
          errors: errors
        }
      },
      onFinish: () => {
        carregando.value = false
      }
    })
  }
  
  function executarTestePersonalizado() {
    try {
      const dados = JSON.parse(form.dadosPersonalizados)
      carregando.value = 'personalizado'
      resultado.value = null
      
      const payload = {
        tipo: 'personalizado',
        webhook_url: form.webhookUrl || null,
        forcar_ambiente: form.forcarAmbiente || null,
        dados: dados
      }
      
      router.post('/teste-n8n/executar', payload, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
          // Resultado virá via props ou flash data
        },
        onError: (errors) => {
          resultado.value = {
            status: 'erro',
            mensagem: 'Erro na requisição',
            errors: errors
          }
        },
        onFinish: () => {
          carregando.value = false
        }
      })
    } catch (e) {
      resultado.value = {
        status: 'erro',
        mensagem: 'JSON inválido nos dados personalizados: ' + e.message
      }
    }
  }
  </script>