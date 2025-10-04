@php($d=$data)
<!doctype html>
<html class="h-full" lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Dashboard</title>
  @vite(['resources/js/mc.js','resources/css/app.css'])
</head>
<body class="min-h-full bg-[#F4F7FB] text-slate-900">
  <div class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex items-end justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold">Dashboard</h1>
        <p class="text-slate-500">Visão geral do período</p>
      </div>
      <form method="get" class="flex items-center gap-2">
        <input type="date" name="start" value="{{ $start->format('Y-m-d') }}" class="border rounded px-2 py-1">
        <input type="date" name="end" value="{{ $end->format('Y-m-d') }}" class="border rounded px-2 py-1">
        <button class="bg-blue-600 hover:bg-blue-700 text-white rounded px-3 py-1">Aplicar</button>
        <a href="{{ route('mc.dashboard') }}" class="text-blue-600 hover:text-blue-700">Limpar</a>
      </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <x-mc.kpi-card title="Faturamento" :value="'R$ '.number_format($d['kpis']['revenue'],2,',','.')" />
      <x-mc.kpi-card title="Ticket Médio" :value="'R$ '.number_format($d['kpis']['avg_ticket'],2,',','.')" />
      <x-mc.kpi-card title="Vendas Confirmadas" :value="$d['kpis']['confirmed_sales']" />
      <x-mc.kpi-card title="Conversão" :value="$d['kpis']['conversion_rate'].'%'" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <x-mc.card>
        <h3 class="text-slate-800 font-semibold mb-3">Funnel</h3>
        <ul class="text-sm text-slate-700 space-y-1">
          <li>Novos clientes: <strong>{{ $d['funnel']['new_clients'] }}</strong></li>
          <li>Reservas criadas: <strong>{{ $d['funnel']['reservations'] }}</strong></li>
          <li>Pagamentos confirmados: <strong>{{ $d['funnel']['payments'] }}</strong></li>
        </ul>
      </x-mc.card>

      <x-mc.card>
        <h3 class="text-slate-800 font-semibold mb-3">Funcionalidades utilizadas</h3>
        <table class="w-full text-sm">
          <thead><tr class="text-left text-slate-500"><th>Feature</th><th>Uso</th></tr></thead>
          <tbody>
          @forelse($d['features'] as $f)
            <tr class="border-t border-slate-200"><td class="py-1">{{ $f['feature'] }}</td><td>{{ $f['count'] }}</td></tr>
          @empty
            <tr><td colspan="2" class="text-slate-500 py-2">Sem dados</td></tr>
          @endforelse
          </tbody>
        </table>
      </x-mc.card>

      <x-mc.chart id="sales_day" title="Faturamento por dia" />
      <x-mc.chart id="sales_hour" title="Faturamento por hora" />

      <x-mc.card>
        <h3 class="text-slate-800 font-semibold mb-3">Vendas por DDD (Top 10)</h3>
        <table class="w-full text-sm">
          <thead><tr class="text-left text-slate-500"><th>DDD</th><th>Vendas</th></tr></thead>
          <tbody>
          @forelse($d['ddd'] as $row)
            <tr class="border-t border-slate-200"><td class="py-1">{{ $row->ddd }}</td><td>{{ $row->c }}</td></tr>
          @empty
            <tr><td colspan="2" class="text-slate-500 py-2">Sem dados</td></tr>
          @endforelse
          </tbody>
        </table>
      </x-mc.card>

      <x-mc.card>
        <h3 class="text-slate-800 font-semibold mb-3">Top compradores</h3>
        <div class="text-sm text-slate-500">Sem dados</div>
      </x-mc.card>
    </div>
  </div>
</body>
</html>

