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
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
