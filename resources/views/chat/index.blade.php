<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ __('Chat') }}</h2></x-slot>
    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-slate-800 shadow sm:rounded-lg p-4">
                <h3 class="font-semibold mb-2">Contatos</h3>
                <ul class="space-y-1 max-h-[60vh] overflow-auto">
                    @foreach($contacts as $c)
                        <li>
                            <a class="block px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-slate-700 {{ request('contact') == $c->id ? 'bg-gray-100 dark:bg-slate-700' : '' }}" href="{{ route('chat.index', ['contact' => $c->id]) }}">
                                {{ $c->name ?? $c->phone ?? 'Contato #'.$c->id }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="md:col-span-2 bg-white dark:bg-slate-800 shadow sm:rounded-lg p-4">
                @if(!$contact)
                    <p class="text-gray-500">Selecione um contato à esquerda.</p>
                @else
                    <h3 class="font-semibold mb-2">{{ $contact->name ?? $contact->phone ?? ('Contato #'.$contact->id) }}</h3>
                    <div id="chat_box" class="border rounded p-3 h-[50vh] overflow-auto bg-gray-50 dark:bg-slate-900"></div>
                    <form id="chat_send_form" class="mt-3 flex gap-2">
                        @csrf
                        <input type="hidden" id="chat_contact" value="{{ $contact->id }}" />
                        <input id="chat_text" type="text" class="flex-1 rounded border-gray-300" placeholder="Digite sua mensagem" />
                        <button class="px-4 py-2 bg-indigo-600 text-white rounded">Enviar</button>
                        <span id="chat_status" class="text-sm text-gray-500"></span>
                    </form>
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            const contactId = Number(document.getElementById('chat_contact').value);
                            const box = document.getElementById('chat_box');
                            const form = document.getElementById('chat_send_form');
                            const input = document.getElementById('chat_text');
                            const status = document.getElementById('chat_status');
                            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                            function bubble(msg) {
                                const wrap = document.createElement('div');
                                wrap.className = 'mb-2 ' + (msg.direction === 'out' ? 'text-right' : '');
                                const b = document.createElement('div');
                                b.className = 'inline-block px-2 py-1 rounded ' + (msg.direction === 'out' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-slate-800 border');
                                b.textContent = msg.body;
                                const t = document.createElement('div');
                                t.className = 'text-[10px] text-gray-400';
                                t.textContent = (msg.sent_at || '').replace('T',' ').slice(0,16);
                                wrap.appendChild(b); wrap.appendChild(t);
                                return wrap;
                            }

                            async function loadMessages(scrollToEnd=false) {
                                try {
                                    const res = await fetch(`{{ route('chat.messages') }}?contact=${contactId}`, { headers: { 'Accept': 'application/json' }});
                                    const data = await res.json();
                                    box.innerHTML = '';
                                    (data.messages || []).forEach(m => box.appendChild(bubble(m)));
                                    if (scrollToEnd) box.scrollTop = box.scrollHeight;
                                } catch {}
                            }

                            async function sendMessage(e) {
                                e.preventDefault();
                                const text = (input.value||'').trim();
                                if (!text) return;
                                status.textContent = 'Enviando...';
                                try {
                                    const res = await fetch(`{{ route('chat.send') }}`, {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                                        body: JSON.stringify({ contact: contactId, text })
                                    });
                                    const data = await res.json();
                                    if (!res.ok || !data.ok) throw new Error(data.error || 'Falha ao enviar');
                                    input.value = '';
                                    await loadMessages(true);
                                    status.textContent = '';
                                } catch (err) {
                                    status.textContent = 'Erro: ' + (err.message || err);
                                }
                            }

                            form.addEventListener('submit', sendMessage);
                            input.addEventListener('keydown', (e) => {
                                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                                    e.preventDefault();
                                    form.requestSubmit();
                                }
                            });

                            loadMessages(true);
                            setInterval(loadMessages, 3000);
                        });
                    </script>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
