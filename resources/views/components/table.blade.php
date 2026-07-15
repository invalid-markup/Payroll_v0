@props(['headers' => [], 'emptyMessage' => 'No records found.'])

<div class="flex flex-col">
    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @foreach($headers as $header)
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ $header }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        {{ $slot }}
                        
                        @if(trim($slot) === '')
                            <tr>
                                <td colspan="{{ count($headers) }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    <x-empty-state :title="$emptyMessage" />
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            
            @if(isset($pagination))
                <div class="mt-4">
                    {{ $pagination }}
                </div>
            @endif
        </div>
    </div>
</div>
