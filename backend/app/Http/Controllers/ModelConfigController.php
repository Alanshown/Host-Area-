<?php

namespace App\Http\Controllers;

use App\Services\ModelConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ModelConfigController extends Controller
{
    public function __construct(
        private ModelConfigService $modelConfig
    ) {
    }

    /**
     * GET /api/model-config
     * 获取当前模型配置
     */
    public function show()
    {
        return response()->json([
            'config' => $this->modelConfig->getCurrentConfig(),
            'supported_models' => $this->modelConfig->getSupportedModels(),
        ]);
    }

    /**
     * PUT /api/model-config
     * 更新模型配置
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:chat,vision',
            'model_id' => 'required|string',
        ]);

        $result = $this->modelConfig->updateModel(
            $validated['type'],
            $validated['model_id']
        );

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * GET /api/model-config/verify
     * 验证配置是否更新成功
     */
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:chat,vision',
            'model_id' => 'required|string',
        ]);

        $isValid = $this->modelConfig->verifyUpdate(
            $validated['type'],
            $validated['model_id']
        );

        return response()->json([
            'verified' => $isValid,
            'config' => $this->modelConfig->getCurrentConfig(),
        ]);
    }
}
