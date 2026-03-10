<script setup>
import { ref, onMounted, nextTick, watch } from 'vue';
import { 
  Send, Trash2, Sparkles, Terminal, History, 
  User, Bot, ChevronRight, Loader2 
} from 'lucide-vue-next';

const messages = ref([]);
const input = ref('');
const isLoading = ref(false);
const isSidebarOpen = ref(true);
const scrollRef = ref(null);

const fetchHistory = async () => {
  const res = await fetch('/api/history');
  messages.value = await res.json();
};

const handleSend = async () => {
  if (!input.value.trim() || isLoading.value) return;

  const userContent = input.value;
  messages.value.push({ role: 'user', content: userContent });
  input.value = '';
  isLoading.value = true;

  // Save to Laravel Backend
  await fetch('/api/history', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ role: 'user', content: userContent }),
  });

  try {
    const res = await fetch('/api/chat', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ prompt: userContent }),
    });
    const data = await res.json();
    
    messages.value.push({ role: 'assistant', content: data.reply });
    await fetch('/api/history', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ role: 'assistant', content: data.reply }),
    });
  } finally {
    isLoading.value = false;
  }
};

const clearHistory = async () => {
  await fetch('/api/history', {
    method: 'DELETE'
  });
  messages.value = [];
};

watch(messages, () => {
  nextTick(() => {
    if (scrollRef.value) scrollRef.value.scrollTop = scrollRef.value.scrollHeight;
  });
}, { deep: true });

onMounted(fetchHistory);
</script>

<template>
  <div class="flex h-screen bg-[#F5F5F5] font-sans text-[#1A1A1A]">
    <!-- Sidebar -->
    <aside 
      :class="['bg-white border-r border-black/5 flex flex-col transition-all duration-300 overflow-hidden', 
               isSidebarOpen ? 'w-[280px] opacity-100' : 'w-0 opacity-0']"
    >
      <div class="p-6 flex items-center gap-3 border-b border-black/5">
        <div class="w-8 h-8 bg-black rounded-lg flex items-center justify-center">
          <Sparkles class="w-5 h-5 text-white" />
        </div>
        <h1 class="font-semibold tracking-tight text-lg">Workspace</h1>
      </div>
      <div class="flex-1 p-4 space-y-2">
        <button class="w-full flex items-center gap-3 px-3 py-2 rounded-xl bg-black/5 text-sm font-medium">
          <Terminal class="w-4 h-4" /> Active Session
        </button>
        <button class="w-full flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-black/5 text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">
          <History class="w-4 h-4" /> Saved Logs
        </button>
      </div>
      <div class="p-4 border-t border-black/5">
        <button @click="clearHistory" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-red-50 text-red-600 text-sm font-medium transition-colors">
          <Trash2 class="w-4 h-4" /> Clear History
        </button>
      </div>
    </aside>

    <!-- Main -->
    <main class="flex-1 flex flex-col min-w-0 relative">
      <header class="h-16 border-b border-black/5 bg-white/80 backdrop-blur-md flex items-center justify-between px-6 sticky top-0 z-10">
        <div class="flex items-center gap-4">
          <button @click="isSidebarOpen = !isSidebarOpen" class="p-2 hover:bg-black/5 rounded-lg transition-colors">
            <ChevronRight :class="['w-5 h-5 transition-transform', isSidebarOpen ? 'rotate-180' : '']" />
          </button>
          <span class="font-medium text-sm text-gray-600">Gemini Flash 3.0</span>
        </div>
        <div class="flex items-center gap-3">
          <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
          <span class="text-xs font-medium text-emerald-600 uppercase tracking-wider">System Online</span>
        </div>
      </header>

      <div ref="scrollRef" class="flex-1 overflow-y-auto p-6 space-y-8 w-full max-w-4xl mx-auto pb-40 relative">
        <div v-for="(msg, i) in messages" :key="i" :class="['flex gap-4', msg.role === 'user' ? 'flex-row-reverse' : '']">
          <div :class="['w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0', 
                        msg.role === 'user' ? 'bg-black text-white' : 'bg-white border border-black/5 text-black']">
            <component :is="msg.role === 'user' ? User : Bot" class="w-4 h-4" />
          </div>
          <div :class="['p-4 text-sm shadow-sm', 
                        msg.role === 'user' ? 'bg-black text-white rounded-2xl rounded-tr-sm max-w-[80%]' : 'bg-white border border-black/5 rounded-2xl rounded-tl-sm w-full']">
            {{ msg.content }}
          </div>
        </div>
        
        <div v-if="isLoading" class="flex gap-4">
          <div class="w-8 h-8 rounded-lg bg-white border border-black/5 text-black flex items-center justify-center flex-shrink-0">
            <Bot class="w-4 h-4" />
          </div>
          <div class="p-4 text-sm bg-white border border-black/5 rounded-2xl rounded-tl-sm shadow-sm flex items-center gap-2">
             <div class="h-2 w-2 bg-gray-300 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
             <div class="h-2 w-2 bg-gray-300 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
             <div class="h-2 w-2 bg-gray-300 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
          </div>
        </div>
      </div>

      <div class="absolute bottom-0 left-0 right-0 p-6 bg-gradient-to-t from-[#F5F5F5] via-[#F5F5F5] to-transparent pointer-events-none">
        <div class="max-w-4xl mx-auto relative pointer-events-auto mt-8">
          <textarea 
            v-model="input" 
            @keydown.enter.exact.prevent="handleSend"
            placeholder="Type your message..."
            class="w-full bg-white border border-black/5 rounded-2xl p-4 pr-16 shadow-lg focus:outline-none focus:ring-2 focus:ring-black/5 text-sm resize-none"
            rows="2"
          />
          <button @click="handleSend" :disabled="!input.trim() || isLoading" class="absolute right-3 bottom-3 p-2 bg-black text-white rounded-xl disabled:opacity-50 transition-opacity">
            <Send class="w-5 h-5 pointer-events-none" />
          </button>
        </div>
      </div>
    </main>
  </div>
</template>
