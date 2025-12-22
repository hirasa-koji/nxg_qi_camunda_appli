<?php

namespace App\Http\Controllers;

use App\Services\Camunda8Service;
use Illuminate\Http\Request;

class CamundaController extends Controller
{
    protected Camunda8Service $camundaService;

    public function __construct(Camunda8Service $camundaService)
    {
        $this->camundaService = $camundaService;
    }

    /**
     * ダッシュボード表示
     */
    public function index()
    {
        try {
            $processes = $this->camundaService->getProcessDefinitions();
            $tasks = $this->camundaService->getTasks();
        } catch (\Exception $e) {
            // エラー時は空配列とエラーメッセージを渡す
            $processes = [];
            $tasks = [];
            session()->flash('error', 'Camunda接続エラー: ' . $e->getMessage());
        }

        return view('camunda.dashboard', compact('processes', 'tasks'));
    }

    /**
     * プロセス開始
     */
    public function startProcess(Request $request)
    {
        $request->validate([
            'bpmn_process_id' => 'required|string',
        ]);

        // JSON形式の変数をパース（簡易実装）
        $variables = [];
        if ($request->filled('json_variables')) {
            $variables = json_decode($request->json_variables, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->with('error', '変数のJSON形式が正しくありません');
            }
        }

        try {
            $this->camundaService->startProcess($request->bpmn_process_id, $variables);
            return back()->with('success', 'プロセスを開始しました');
        } catch (\Exception $e) {
            return back()->with('error', '開始失敗: ' . $e->getMessage());
        }
    }

    /**
     * タスク完了
     */
    public function completeTask(Request $request, string $taskId)
    {
        try {
            $this->camundaService->completeTask($taskId, []);
            return back()->with('success', 'タスクを完了しました');
        } catch (\Exception $e) {
            return back()->with('error', '完了失敗: ' . $e->getMessage());
        }
    }
}