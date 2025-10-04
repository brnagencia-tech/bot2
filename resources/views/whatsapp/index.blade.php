<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('WhatsApp (Conexão Web)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-gray-500">Status</div>
                            <div id="wa_status" class="text-lg font-medium">Carregando...</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button id="wa_refresh" class="px-3 py-2 bg-gray-100 rounded border">Atualizar</button>
                            <form id="wa_logout_form" method="post" action="{{ route('whatsapp.logout') }}">
                                @csrf
                                <button type="submit" class="px-3 py-2 bg-red-600 text-white rounded">Desconectar</button>
                            </form>
                        </div>
                    </div>

                    <div class="mt-6">
                        <div class="text-sm text-gray-500 mb-2">QR Code de Conexão</div>
                        <div id="qr_wrap" class="p-4 bg-gray-50 rounded border flex items-center justify-center min-h-[280px]">
                            <div id="qr_placeholder" class="text-gray-500">Se não estiver conectado, um QR aparecerá aqui.</div>
                            <img id="qr_img" src="" alt="QR" class="hidden max-w-xs rounded border" />
                        </div>
                        <div class="text-sm text-gray-500 mt-2">Abra o WhatsApp no celular → Ajustes/Dispositivos Conectados → Conectar um dispositivo → Escaneie o QR.</div>
                    </div>

                    <div class="mt-8">
                        <h3 class="text-lg font-semibold mb-2">Enviar mensagem de teste</h3>
                        <div class="p-4 bg-gray-50 rounded border">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Telefone (com DDI/DD)</label>
                                    <input id="wa_to" type="text" class="mt-1 block w-full rounded border-gray-300" placeholder="Ex.: 5511999999999" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Mensagem</label>
                                    <input id="wa_text" type="text" class="mt-1 block w-full rounded border-gray-300" placeholder="Mensagem de teste" />
                                </div>
                            </div>
                            <div class="mt-3 flex items-center gap-2">
                                <button id="wa_send" class="px-4 py-2 bg-indigo-600 text-white rounded">Enviar</button>
                                <span id="wa_send_status" class="text-sm text-gray-500"></span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 text-xs text-gray-500">
                        Aviso: integração não-oficial via WhatsApp Web. Sujeita a mudanças/bloqueios da plataforma. Use com responsabilidade.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const statusEl = document.getElementById('wa_status');
            const qrImg = document.getElementById('qr_img');
            const qrPh = document.getElementById('qr_placeholder');
            const refreshBtn = document.getElementById('wa_refresh');
            const logoutForm = document.getElementById('wa_logout_form');
            const sendBtn = document.getElementById('wa_send');
            const inputTo = document.getElementById('wa_to');
            const inputText = document.getElementById('wa_text');
            const sendStatus = document.getElementById('wa_send_status');

            async function fetchStatus() {
                try {
                    const res = await fetch("{{ route('whatsapp.status') }}", { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    statusEl.textContent = (data.connected ? 'Conectado' : (data.state || 'Desconectado'));
                } catch (e) {
                    statusEl.textContent = 'Indisponível';
                }
            }

            async function fetchQR() {
                try {
                    const res = await fetch("{{ route('whatsapp.qr') }}", { headers: { 'Accept': 'application/json' } });
                    if (res.status === 204) {
                        // conectado: sem QR
                        qrImg.classList.add('hidden');
                        qrPh.classList.remove('hidden');
                        qrPh.textContent = 'Conectado. Nenhum QR disponível.';
                        return;
                    }
                    const data = await res.json();
                    if (data && data.data_url) {
                        qrImg.src = data.data_url;
                        qrImg.classList.remove('hidden');
                        qrPh.classList.add('hidden');
                    } else {
                        qrImg.classList.add('hidden');
                        qrPh.classList.remove('hidden');
                        qrPh.textContent = 'Aguardando QR...';
                    }
                } catch (e) {
                    qrImg.classList.add('hidden');
                    qrPh.classList.remove('hidden');
                    qrPh.textContent = 'Serviço indisponível';
                }
            }

            async function doLogout(e) {
                e.preventDefault();
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                try {
                    await fetch("{{ route('whatsapp.logout') }}", { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' } });
                    await fetchStatus();
                    await fetchQR();
                } catch {}
            }

            refreshBtn.addEventListener('click', async () => { await fetchStatus(); await fetchQR(); });
            logoutForm.addEventListener('submit', doLogout);
            sendBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const to = (inputTo.value || '').trim();
                const text = (inputText.value || '').trim();
                if (!to || !text) {
                    sendStatus.textContent = 'Preencha telefone e mensagem.';
                    return;
                }
                sendStatus.textContent = 'Enviando...';
                try {
                    const res = await fetch("{{ route('whatsapp.send') }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                        body: JSON.stringify({ to, text })
                    });
                    const data = await res.json();
                    if (!res.ok || !data.ok) throw new Error(data.error || 'Falha ao enviar');
                    sendStatus.textContent = 'Mensagem enviada (id: ' + (data.id || 'n/d') + ')';
                } catch (err) {
                    sendStatus.textContent = 'Erro: ' + (err.message || err);
                }
            });

            fetchStatus();
            fetchQR();
            setInterval(() => { fetchStatus(); fetchQR(); }, 5000);
        });
    </script>
</x-app-layout>
