<x-mc.card>
  <div class="flex items-center justify-between mb-3">
    <h3 class="text-slate-800 font-semibold">{{ $title }}</h3>
  </div>
  <canvas data-chart="{{ $id }}" class="w-full h-48"></canvas>
</x-mc.card>
