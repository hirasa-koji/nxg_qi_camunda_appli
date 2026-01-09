<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Log;

class Camunda8Service
{
  /**
     * OAuthトークンを取得する（キャッシュ付き）
     */
    protected function getAccessToken()
    {
        // トークンをキャッシュしてAPI負荷を減らす（有効期限より少し短めに設定）
        return Cache::remember('camunda_token', 300, function () {
            $response = Http::asForm()->post(env('CAMUNDA_AUTH_URL'), [
#               'grant_type' => 'password',
                'grant_type' => 'client_credentials',
                'username' => 'hirasa_koji',
                'password' => '##00Marinyan',
               
                'client_id' => env('CAMUNDA_CLIENT_ID'),
#               'audience' => 'tasklist.camunda.io',
                'client_secret' => env('CAMUNDA_CLIENT_SECRET'),
                // SaaSの場合は audience パラメータが必要な場合があります
            ]);

            if ($response->failed()) {
                Log::error('Camunda Auth Failed: ' . $response->body());
                throw new \Exception('Camunda Auth Failed: ' . $response->body());
            }

        #    dd($response);
            Log::info('Camunda Auth success.  access_token:' . $response->json()['access_token']);
            return $response->json()['access_token'];
        });
    }

    /**
     * 共通のHTTPクライアント生成（Bearer Token付与）
     */
    protected function getClient()
    {
        $token = $this->getAccessToken();
        return Http::withToken($token)->acceptJson();
    }

    /**
     * [Operate API] プロセス定義一覧を取得
     * C8ではOperateに対して検索クエリを投げます
     */
    public function getProcessDefinitions()
    {
        $url = rtrim(env('CAMUNDA_OPERATE_URL'), '/') . '/process-definitions/search';
        
        $response = $this->getClient()->post($url, [
            'filter' => [], // 必要に応じてフィルタリング
            'size' => 50,
            'sort' => [['field' => 'name', 'order' => 'ASC']]
        ]);

        return $response->json()['items'] ?? [];
    }

    /**
     * [Tasklist API] タスク一覧を取得
     * C8ではTasklistに対して検索クエリを投げます
     */
    public function getTasks()
    {
#        $url = rtrim(env('CAMUNDA_TASKLIST_URL'), '/') . '/tasks/search';
        $url = rtrim(env('CAMUNDA_TASKLIST_URL'), '/') . '/search';

        Log::debug("getTasks url:" . $url );
        $response = $this->getClient()->post($url, [
            'state' => 'CREATED', // 未完了タスクのみ
#           'assigned' => false,  // 未割当などをフィルタ可能
            // 'assignee' => 'demo', // 特定ユーザーで絞る場合
        ]);
        if ($response->failed()) {
            Log::error('getTasks Failed: ' . $response->body());
            throw new \Exception('getTasks ' . $response->body());
        }

#        Log::debug("getTasks response:" . $response->json() );
#        dd($response);
        return $response->json() ?? [];
    }

    /**
     * [Zeebe API] プロセスを開始する
     * C8ではEngine(Zeebe)に対してコマンドを送ります
     */
    public function startProcess(string $bpmnProcessId, array $variables = [])
    {
        $url = rtrim(env('CAMUNDA_ZEEBE_URL'), '/') . '/process-instances';

        // C8への変数は純粋なJSONオブジェクトでOK（型指定不要）
        $response = $this->getClient()->post($url, [
            'bpmnProcessId' => $bpmnProcessId,
            'version' => -1, // -1 は最新バージョンを意味する
            'variables' => $variables
        ]);

        if ($response->failed()) {
            throw new \Exception('Start Process Failed: ' . $response->body());
        }

        return $response->json();
    }
    /**
     * 前回のコードにこのメソッドを追加してください
     * [Tasklist API] タスクを完了する
     */
    public function completeTask(string $taskId, array $variables = [])
    {
        $url = rtrim(env('CAMUNDA_TASKLIST_URL'), '/') . "/tasks/{$taskId}/complete";

        // C8 Tasklist APIでは変数を 'variables' 配列で渡す
        $response = $this->getClient()->patch($url, [
            'variables' => $variables
        ]);

        if ($response->failed()) {
            throw new \Exception('Task Completion Failed: ' . $response->body());
        }

        return $response->json();
    }
    
    // 省略されたメソッドの再掲が必要な場合はおっしゃってください
    // ここではコンテキスト維持のため省略しています
    
    // 必須メソッド再掲（簡略版）
}