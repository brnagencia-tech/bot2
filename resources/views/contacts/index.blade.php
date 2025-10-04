<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ __('Contatos') }}</h2></x-slot>
    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 shadow sm:rounded-lg p-4">
                <table class="min-w-full text-sm">
                    <thead><tr><th class="text-left p-2">Nome</th><th class="text-left p-2">Telefone</th><th class="text-left p-2">Ações</th></tr></thead>
                    <tbody>
                        @forelse($contacts as $c)
                        <tr class="border-t border-gray-200 dark:border-slate-700">
                            <td class="p-2">{{ $c->name ?? '-' }}</td>
                            <td class="p-2">{{ $c->phone ?? '-' }}</td>
                            <td class="p-2"><a class="text-indigo-600" href="{{ route('chat.index', ['contact' => $c->id]) }}">Abrir chat</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="p-4 text-gray-500">Nenhum contato ainda.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $contacts->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>

