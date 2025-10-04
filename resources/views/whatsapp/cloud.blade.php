<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('WhatsApp Cloud (Oficial)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold">Configuração</h3>
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div class="p-3 bg-gray-50 rounded border">
                            <div class="text-gray-500">Webhook URL</div>
                            <div class="font-mono break-all">{{ $config['webhook_url'] }}</div>
                        </div>
                        <div class="p-3 bg-gray-50 rounded border">
                            <div class="text-gray-500">Verify Token</div>
                            <div class="font-mono break-all">{{ $config['verify_token'] ?? 'defina no .env (WHATSAPP_VERIFY_TOKEN)' }}</div>
                        </div>
                        <div class="p-3 bg-gray-50 rounded border">
                            <div class="text-gray-500">Phone Number ID</div>
                            <div class="font-mono break-all">{{ $config['phone_number_id'] ?? 'defina no .env (WHATSAPP_PHONE_NUMBER_ID)' }}</div>
                        </div>
                        <div class="p-3 bg-gray-50 rounded border">
                            <div class="text-gray-500">Graph API Version</div>
                            <div class="font-mono break-all">{{ $config['graph_version'] }}</div>
                        </div>
                        <div class="p-3 bg-gray-50 rounded border">
                            <div class="text-gray-500">Token de Acesso</div>
                            <div>{{ $config['token_present'] ? 'Configurado' : 'Não configurado (WHATSAPP_TOKEN)' }}</div>
                        </div>
                    </div>

                    <div class="mt-6 text-sm text-gray-600">
                        Em Meta Developers (WhatsApp):
                        <ol class="list-decimal ms-5">
                            <li>Defina o <strong>Callback URL</strong> como acima e o mesmo <strong>Verify Token</strong>.</li>
                            <li>Assine o tópico <strong>messages</strong>.</li>
                            <li>Preencha <strong>WHATSAPP_TOKEN</strong> e <strong>WHATSAPP_PHONE_NUMBER_ID</strong> no .env do servidor.</li>
                        </ol>
                    </div>

                    <div class="mt-8">
                        <h3 class="text-lg font-semibold mb-2">Enviar mensagem de teste</h3>
                        <div class="p-4 bg-gray-50 rounded border">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Telefone (com DDI/DD)</label>
                                    <input id="cloud_to" type="text" class="mt-1 block w-full rounded border-gray-300" placeholder="Ex.: 5511999999999" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Mensagem</label>
                                    <input id="cloud_text" type="text" class="mt-1 block w-full rounded border-gray-300" placeholder="Mensagem de teste" />
                                </div>
                            </div>
                            <div class="mt-3 flex items-center gap-2">
                                <button id="cloud_send" class="px-4 py-2 bg-indigo-600 text-white rounded">Enviar</button>
                                <span id="cloud_status" class="text-sm text-gray-500"></span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('cloud_send');
            const inpTo = document.getElementById('cloud_to');
            const inpText = document.getElementById('cloud_text');
            const status = document.getElementById('cloud_status');
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const to = (inpTo.value||'').trim();
                const text = (inpText.value||'').trim();
                if (!to || !text) { status.textContent = 'Preencha telefone e mensagem.'; return; }
                status.textContent = 'Enviando...';
                try {
                    const res = await fetch("{{ route('whatsapp.cloud.send') }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                        body: JSON.stringify({ to, text })
                    });
                    const data = await res.json();
                    if (!res.ok || !data.ok) throw new Error((data && (data.error || data.status)) || 'Falha');
                    status.textContent = 'Mensagem enviada com sucesso!';
                } catch (err) {
                    status.textContent = 'Erro: ' + (err.message || err);
                }
            });
        });
    </script>
</x-app-layout>

