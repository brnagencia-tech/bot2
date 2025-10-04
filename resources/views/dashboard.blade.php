<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard - BotWhatsApp') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __('Você está logado! Bem-vindo ao BotWhatsApp.') }}
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="p-4 bg-gray-50 rounded border">
                            <div class="text-sm text-gray-500">Status</div>
                            <div class="text-lg font-medium">Em configuração</div>
                        </div>
                        <div class="p-4 bg-gray-50 rounded border">
                            <div class="text-sm text-gray-500">Conexões WhatsApp</div>
                            <div class="text-lg font-medium">0</div>
                        </div>
                        <div class="p-4 bg-gray-50 rounded border">
                            <div class="text-sm text-gray-500">Mensagens Hoje</div>
                            <div class="text-lg font-medium">0</div>
                        </div>
                    </div>
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold mb-2">Teste rápido da IA</h3>
                        <div class="p-4 bg-gray-50 rounded border">
                            <label for="ai_message" class="block text-sm font-medium text-gray-700">Mensagem</label>
                            <textarea id="ai_message" class="mt-1 block w-full rounded border-gray-300" rows="3" placeholder="Digite sua mensagem para a IA..."></textarea>
                            <div class="mt-3 flex items-center gap-2">
                                <button id="ai_send" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">Enviar</button>
                                <span id="ai_status" class="text-sm text-gray-500"></span>
                            </div>
                            <div id="ai_reply_wrap" class="mt-4 hidden">
                                <div class="text-sm text-gray-500">Resposta da IA</div>
                                <div id="ai_reply" class="mt-1 p-3 bg-white rounded border"></div>
                            </div>
                        </div>
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            const btn = document.getElementById('ai_send');
                            const ta = document.getElementById('ai_message');
                            const statusEl = document.getElementById('ai_status');
                            const replyWrap = document.getElementById('ai_reply_wrap');
                            const replyEl = document.getElementById('ai_reply');
                            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                            async function send() {
                                const msg = (ta.value || '').trim();
                                if (!msg) { ta.focus(); return; }
                                statusEl.textContent = 'Enviando...';
                                btn.disabled = true;
                                replyWrap.classList.add('hidden');
                                try {
                                    const res = await fetch("{{ route('ai.generate') }}", {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': token,
                                            'Accept': 'application/json'
                                        },
                                        body: JSON.stringify({ message: msg })
                                    });
                                    const data = await res.json();
                                    if (!res.ok) {
                                        throw new Error((data && (data.error || data.message)) || 'Erro na IA');
                                    }
                                    replyEl.textContent = data.reply || '';
                                    replyWrap.classList.remove('hidden');
                                    statusEl.textContent = '';
                                } catch (e) {
                                    statusEl.textContent = 'Falha: ' + (e.message || e);
                                } finally {
                                    btn.disabled = false;
                                }
                            }

                            btn.addEventListener('click', (e) => { e.preventDefault(); send(); });
                            ta.addEventListener('keydown', (e) => {
                                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                                    e.preventDefault();
                                    send();
                                }
                            });
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
