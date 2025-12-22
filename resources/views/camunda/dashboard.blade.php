<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Camunda Dashboard') }}
        </h2>
    </x-slot>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <div class="py-12" x-data="{ activeTab: 'tasks', showModal: false, selectedProcess: '', selectedProcessName: '' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <div class="flex border-b mb-4">
                        <button @click="activeTab = 'tasks'" 
                                :class="{ 'border-b-2 border-indigo-500 text-indigo-600': activeTab === 'tasks' }"
                                class="px-4 py-2 text-sm font-medium focus:outline-none">
                            マイタスク一覧
                        </button>
                        <button @click="activeTab = 'processes'" 
                                :class="{ 'border-b-2 border-indigo-500 text-indigo-600': activeTab === 'processes' }"
                                class="px-4 py-2 text-sm font-medium focus:outline-none text-gray-500 hover:text-gray-700">
                            プロセス定義一覧
                        </button>
                    </div>

                    <div x-show="activeTab === 'tasks'">
                        <h3 class="text-lg font-bold mb-3">未完了タスク</h3>
                        @if(empty($tasks))
                            <p class="text-gray-500">現在、タスクはありません。</p>
                        @else
                            <table class="min-w-full leading-normal">
                                <thead>
                                    <tr>
                                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">タスク名</th>
                                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">プロセス名</th>
                                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">作成日時</th>
                                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tasks as $task)
                                    <tr>
                                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                            {{ $task['name'] ?? 'No Name' }}
                                        </td>
                                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                            {{ $task['processDefinitionId'] ?? '-' }}
                                        </td>
                                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                            {{ \Carbon\Carbon::parse($task['creationTime'])->timezone('Asia/Tokyo')->format('Y-m-d H:i') }}
                                        </td>
                                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                            <form action="{{ route('task.complete', $task['id']) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-1 px-3 rounded text-xs">
                                                    完了する
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>

                    <div x-show="activeTab === 'processes'" style="display: none;">
                        <h3 class="text-lg font-bold mb-3">開始可能なプロセス</h3>
                        <table class="min-w-full leading-normal">
                            <thead>
                                <tr>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">プロセス名</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID (Key)</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">バージョン</th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($processes as $process)
                                <tr>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                        {{ $process['name'] ?? 'No Name' }}
                                    </td>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                        <span class="bg-gray-200 rounded px-2 py-1 text-xs font-mono">{{ $process['bpmnProcessId'] }}</span>
                                    </td>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                        v{{ $process['version'] }}
                                    </td>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                        <button @click="showModal = true; selectedProcess = '{{ $process['bpmnProcessId'] }}'; selectedProcessName = '{{ $process['name'] ?? $process['bpmnProcessId'] }}'" 
                                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded text-xs">
                                            開始フォームを開く
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        <div x-show="showModal" class="fixed z-10 inset-0 overflow-y-auto" style="display: none;">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form action="{{ route('process.start') }}" method="POST">
                        @csrf
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        プロセス開始: <span x-text="selectedProcessName"></span>
                                    </h3>
                                    <div class="mt-4">
                                        <input type="hidden" name="bpmn_process_id" x-model="selectedProcess">
                                        
                                        <label class="block text-gray-700 text-sm font-bold mb-2">初期変数 (JSON形式)</label>
                                        <textarea name="json_variables" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" rows="4" placeholder='{"amount": 1000, "reason": "test"}'></textarea>
                                        <p class="text-xs text-gray-500 mt-1">※空の場合は変数は送信されません</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                                開始する
                            </button>
                            <button type="button" @click="showModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                キャンセル
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
    </div>
</x-app-layout>