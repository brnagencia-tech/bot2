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

            fetchStatus();
            fetchQR();
            setInterval(() => { fetchStatus(); fetchQR(); }, 5000);
        });
    </script>
</x-app-layout>

