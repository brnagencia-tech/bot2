<!doctype html>
<html class="h-full" lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Inbox</title>
  @vite(['resources/js/mc.js','resources/css/app.css'])
</head>
<body class="min-h-full bg-[#F4F7FB] text-slate-900">
  <div class="max-w-7xl mx-auto px-4 py-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-1 bg-white border border-slate-200 rounded-2xl shadow-sm p-4">
      <h2 class="font-semibold mb-3">Contatos</h2>
      <input class="w-full border rounded px-2 py-1 mb-3" placeholder="Buscar..."/>
      <ul class="space-y-2 max-h-[70vh] overflow-auto">
        @foreach($contacts as $c)
          <li class="text-sm text-slate-700">{{ $c->name ?? $c->phone ?? $c->wa_id }}</li>
        @endforeach
      </ul>
    </div>
    <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl shadow-sm p-4 flex flex-col h-[80vh]">
      <div class="flex items-center justify-between mb-3">
        <div class="text-sm">
          <span class="font-medium">WhatsApp:</span>
          <span id="wa-status" class="ml-2 px-2 py-0.5 rounded text-white text-xs"
            :class="waConnected ? 'bg-green-600' : 'bg-amber-500'">{{ $waStatus['connected'] ? 'Conectado' : 'Não conectado' }}</span>
        </div>
        <div>
          @if(\Illuminate\Support\Facades\Route::has('mc.dashboard'))
            <a href="{{ route('mc.dashboard') }}" class="text-blue-600 hover:text-blue-700 text-sm">Dashboard</a>
          @endif
        </div>
      </div>

      @if(empty($waStatus['hasCreds']))
        <div class="mb-4">
          <div class="text-sm text-slate-600 mb-2">Escaneie o QR Code para conectar:</div>
          <img id="wa-qr" src="{{ route('mc.wa.qr') }}" class="border rounded" alt="QR Code">
        </div>
      @endif

      <div class="flex-1 border border-slate-200 rounded p-3 mb-3 overflow-auto bg-slate-50">
        <div class="text-slate-500 text-sm">Selecione um contato para iniciar.</div>
      </div>

      <form id="send-form" class="flex gap-2" onsubmit="return sendMessage(event)">
        <input name="to" class="border rounded px-2 py-1 w-40" placeholder="55DDDNNNNNNN"/>
        <input name="text" class="border rounded px-2 py-1 flex-1" placeholder="Digite uma mensagem... (/ai para IA)"/>
        <button class="bg-blue-600 hover:bg-blue-700 text-white rounded px-3">Enviar</button>
      </form>
    </div>
  </div>
  <script>
    async function sendMessage(e){
      e.preventDefault();
      const fd = new FormData(e.target);
      const to = fd.get('to');
      const text = fd.get('text');
      const res = await fetch('{{ route('mc.message.send') }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}, body: JSON.stringify({to, text})});
      if(!res.ok){ alert('Falha ao enviar'); return; }
      e.target.reset();
    }

    // Poll WhatsApp status/QR
    async function pollWA(){
      try {
        const s = await fetch('{{ route('mc.wa.status') }}', {headers:{'Accept':'application/json'}});
        if(s.ok){
          const j = await s.json();
          if(j.connected){ document.getElementById('wa-qr')?.remove(); }
        }
      } catch(e) {}
      setTimeout(pollWA, 5000);
    }
    pollWA();
  </script>
</body>
</html>

