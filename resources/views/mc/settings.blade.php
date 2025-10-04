<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Configurações</title>
  @vite(['resources/css/app.css'])
</head>
<body class="min-h-full bg-[#F4F7FB] text-slate-900">
  <div class="max-w-2xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-4">Configurações do Workspace</h1>
    @if(session('status'))<div class="mb-3 text-green-700">Salvo.</div>@endif
    <form method="post" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 space-y-3">
      @csrf
      <div>
        <label class="block text-sm text-slate-600">Nome</label>
        <input name="workspace_name" value="{{ $settings['workspace_name'] ?? '' }}" class="w-full border rounded px-2 py-1" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Fuso</label>
        <input name="timezone" value="{{ $settings['timezone'] ?? '' }}" class="w-full border rounded px-2 py-1" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Idioma</label>
        <input name="language" value="{{ $settings['language'] ?? '' }}" class="w-full border rounded px-2 py-1" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Modelo OpenAI</label>
        <input name="openai_model" value="{{ $settings['openai_model'] ?? env('OPENAI_MODEL','gpt-4o-mini') }}" class="w-full border rounded px-2 py-1" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">OpenAI API Key (opcional)</label>
        <input name="openai_api_key" value="{{ $settings['openai_api_key'] ?? '' }}" class="w-full border rounded px-2 py-1" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">WhatsApp Service URL</label>
        <input name="whatsapp_service_url" value="{{ $settings['whatsapp_service_url'] ?? env('WHATSAPP_SERVICE_URL','http://127.0.0.1:3001') }}" class="w-full border rounded px-2 py-1" />
      </div>
      <div>
        <button class="bg-blue-600 hover:bg-blue-700 text-white rounded px-3 py-1">Salvar</button>
      </div>
    </form>
  </div>
</body>
</html>

