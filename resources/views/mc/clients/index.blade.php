<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Clientes</title>
  @vite(['resources/css/app.css'])
</head>
<body class="min-h-full bg-[#F4F7FB] text-slate-900">
  <div class="max-w-6xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-4">Clientes</h1>
    <form method="get" class="flex items-center gap-2 mb-4">
      <input type="text" name="ddd" value="{{ request('ddd') }}" placeholder="DDD" class="border rounded px-2 py-1 w-24" />
      <button class="bg-blue-600 hover:bg-blue-700 text-white rounded px-3">Filtrar</button>
    </form>
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm">
      <table class="w-full text-sm">
        <thead class="text-left text-slate-500"><tr><th class="p-3">Nome</th><th>Telefone</th><th>WA</th></tr></thead>
        <tbody>
        @foreach($contacts as $c)
          <tr class="border-t border-slate-200"><td class="p-3">{{ $c->name ?? '-' }}</td><td>{{ $c->phone ?? '-' }}</td><td class="text-xs text-slate-500">{{ $c->wa_id }}</td></tr>
        @endforeach
        </tbody>
      </table>
    </div>
    <div class="mt-3">{{ $contacts->links() }}</div>
  </div>
</body>
</html>

