<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModelRegistrySeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->toDateTimeString();
        $models = $this->allModels();

        foreach ($models as $m) {
            DB::table('model_registry')->insert(array_merge($m, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    private function allModels(): array
    {
        return array_merge(
            $this->llmModels(),
            $this->imageGenModels(),
            $this->consistencyModels(),
            $this->imageEnhanceModels(),
            $this->image2VideoModels(),
            $this->videoEnhanceModels(),
            $this->ttsModels(),
            $this->musicModels(),
            $this->asrModels(),
            $this->moderationModels(),
        );
    }

    // ─── Category 1: LLM (环节1/2/3) ────────────────────────────
    private function llmModels(): array
    {
        return [
            [
                'category' => 'llm', 'model_name' => 'claude-sonnet-4-6', 'display_name' => 'Claude Sonnet 4.6',
                'provider' => 'Anthropic', 'api_type' => 'anthropic',
                'base_url' => 'https://api.anthropic.com', 'request_path' => '/v1/messages',
                'default_params' => json_encode(['max_tokens' => 4096, 'temperature' => 0.7]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '文案解析、分镜规划（结构化输出强）。需额外 Header: anthropic-version: 2023-06-01',
                'sort_order' => 1,
            ],
            [
                'category' => 'llm', 'model_name' => 'gpt-5', 'display_name' => 'GPT-5',
                'provider' => 'OpenAI', 'api_type' => 'openai',
                'base_url' => 'https://api.openai.com', 'request_path' => '/v1/chat/completions',
                'default_params' => json_encode(['model' => 'gpt-5', 'temperature' => 0.7]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '全场景，最新旗舰模型',
                'sort_order' => 2,
            ],
            [
                'category' => 'llm', 'model_name' => 'gpt-4o', 'display_name' => 'GPT-4o',
                'provider' => 'OpenAI', 'api_type' => 'openai',
                'base_url' => 'https://api.openai.com', 'request_path' => '/v1/chat/completions',
                'default_params' => json_encode(['model' => 'gpt-4o', 'temperature' => 0.7]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '全场景，性价比高',
                'sort_order' => 3,
            ],
            [
                'category' => 'llm', 'model_name' => 'gemini-3.1-pro', 'display_name' => 'Gemini 3.1 Pro',
                'provider' => 'Google', 'api_type' => 'gemini',
                'base_url' => 'https://generativelanguage.googleapis.com', 'request_path' => '/v1beta/models/gemini-pro:generateContent',
                'default_params' => json_encode(['temperature' => 0.7]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '全场景，Key通过Query参数传递',
                'sort_order' => 4,
            ],
            [
                'category' => 'llm', 'model_name' => 'deepseek-v4', 'display_name' => 'DeepSeek V4',
                'provider' => 'DeepSeek', 'api_type' => 'openai',
                'base_url' => 'https://api.deepseek.com', 'request_path' => '/v1/chat/completions',
                'default_params' => json_encode(['model' => 'deepseek-v4', 'temperature' => 0.7]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => 'OpenAI兼容协议，高性价比',
                'sort_order' => 5,
            ],
            [
                'category' => 'llm', 'model_name' => 'deepseek-r1', 'display_name' => 'DeepSeek R1',
                'provider' => 'DeepSeek', 'api_type' => 'openai',
                'base_url' => 'https://api.deepseek.com', 'request_path' => '/v1/chat/completions',
                'default_params' => json_encode(['model' => 'deepseek-r1', 'reasoning_effort' => 'medium']),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '推理型，适合复杂文案解析',
                'sort_order' => 6,
            ],
            [
                'category' => 'llm', 'model_name' => 'kimi-k2', 'display_name' => 'Kimi K2',
                'provider' => '月之暗面 Moonshot', 'api_type' => 'openai',
                'base_url' => 'https://api.moonshot.cn', 'request_path' => '/v1/chat/completions',
                'default_params' => json_encode(['model' => 'kimi-k2', 'temperature' => 0.7]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '长文案解析（上下文窗口大）',
                'sort_order' => 7,
            ],
            [
                'category' => 'llm', 'model_name' => 'qwen-3.6-max', 'display_name' => 'Qwen 3.6 Max',
                'provider' => '阿里云', 'api_type' => 'openai',
                'base_url' => 'https://dashscope.aliyuncs.com/compatible-mode', 'request_path' => '/v1/chat/completions',
                'default_params' => json_encode(['model' => 'qwen-3.6-max', 'temperature' => 0.7]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '中文文案优化，阿里云百炼 OpenAI兼容',
                'sort_order' => 8,
            ],
            [
                'category' => 'llm', 'model_name' => 'doubao-pro', 'display_name' => '豆包 Doubao Pro',
                'provider' => '字节跳动', 'api_type' => 'openai',
                'base_url' => 'https://ark.cn-beijing.volces.com', 'request_path' => '/api/v3/chat/completions',
                'default_params' => json_encode(['model' => 'doubao-pro', 'temperature' => 0.7]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '中文文案、分镜。需先在火山引擎创建Endpoint',
                'sort_order' => 9,
            ],
            [
                'category' => 'llm', 'model_name' => 'ernie-5.0', 'display_name' => 'ERNIE 5.0',
                'provider' => '百度', 'api_type' => 'openai',
                'base_url' => 'https://qianfan.baidubce.com', 'request_path' => '/v2/chat/completions',
                'default_params' => json_encode(['model' => 'ernie-5.0', 'temperature' => 0.7]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '中文创作。百度千帆V2 OpenAI兼容',
                'sort_order' => 10,
            ],
            [
                'category' => 'llm', 'model_name' => 'glm-5', 'display_name' => 'GLM-5',
                'provider' => '智谱 Zhipu', 'api_type' => 'openai',
                'base_url' => 'https://open.bigmodel.cn', 'request_path' => '/api/paas/v4/chat/completions',
                'default_params' => json_encode(['model' => 'glm-5', 'temperature' => 0.7]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '全场景，智谱 OpenAI兼容',
                'sort_order' => 11,
            ],
            [
                'category' => 'llm', 'model_name' => 'mistral-large-3', 'display_name' => 'Mistral Large 3',
                'provider' => 'Mistral', 'api_type' => 'openai',
                'base_url' => 'https://api.mistral.ai', 'request_path' => '/v1/chat/completions',
                'default_params' => json_encode(['model' => 'mistral-large-3', 'temperature' => 0.7]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '全场景，OpenAI兼容',
                'sort_order' => 12,
            ],
            [
                'category' => 'llm', 'model_name' => 'yi-lightning', 'display_name' => 'Yi-Lightning',
                'provider' => '零一万物 01.AI', 'api_type' => 'openai',
                'base_url' => 'https://api.lingyiwanwu.com', 'request_path' => '/v1/chat/completions',
                'default_params' => json_encode(['model' => 'yi-lightning', 'temperature' => 0.7]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '全场景 [端点待确认]',
                'sort_order' => 13,
            ],
            [
                'category' => 'llm', 'model_name' => 'grok-4', 'display_name' => 'Grok 4',
                'provider' => 'xAI', 'api_type' => 'openai',
                'base_url' => 'https://api.x.ai', 'request_path' => '/v1/chat/completions',
                'default_params' => json_encode(['model' => 'grok-4', 'temperature' => 0.7]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '创意文案，OpenAI兼容',
                'sort_order' => 14,
            ],
            [
                'category' => 'llm', 'model_name' => 'llama-4-groq', 'display_name' => 'Llama 4 (Groq)',
                'provider' => 'Meta via Groq', 'api_type' => 'openai',
                'base_url' => 'https://api.groq.com', 'request_path' => '/openai/v1/chat/completions',
                'default_params' => json_encode(['model' => 'llama-4', 'temperature' => 0.7]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '开源替代，通过Groq托管',
                'sort_order' => 15,
            ],
            [
                'category' => 'llm', 'model_name' => 'llama-4-fireworks', 'display_name' => 'Llama 4 (Fireworks)',
                'provider' => 'Meta via Fireworks', 'api_type' => 'openai',
                'base_url' => 'https://api.fireworks.ai', 'request_path' => '/inference/v1/chat/completions',
                'default_params' => json_encode(['model' => 'llama-4', 'temperature' => 0.7]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '开源替代，通过Fireworks托管',
                'sort_order' => 16,
            ],
        ];
    }

    // ─── Category 2: Image Gen (环节4) ──────────────────────────
    private function imageGenModels(): array
    {
        return [
            [
                'category' => 'image_gen', 'model_name' => 'kling-image-o1', 'display_name' => '可灵 Image O1',
                'provider' => '快手 Kling', 'api_type' => 'kling',
                'base_url' => 'https://api-beijing.klingai.com', 'request_path' => '/v1/images/generations',
                'default_params' => json_encode(['n' => 1]),
                'required_fields' => json_encode([
                    ['key' => 'access_key', 'label' => 'Access Key ID', 'type' => 'password'],
                    ['key' => 'access_secret', 'label' => 'Access Key Secret', 'type' => 'password'],
                ]),
                'description' => 'JWT HS256签名认证（AccessKey+Secret）',
                'sort_order' => 1,
            ],
            [
                'category' => 'image_gen', 'model_name' => 'gpt-image-2', 'display_name' => 'GPT-Image-2',
                'provider' => 'OpenAI', 'api_type' => 'openai',
                'base_url' => 'https://api.openai.com', 'request_path' => '/v1/images/generations',
                'default_params' => json_encode(['model' => 'gpt-image-2', 'n' => 1, 'quality' => 'hd']),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '2026.3起 DALL-E 品牌已退役，由 GPT-Image-2 取代',
                'sort_order' => 2,
            ],
            [
                'category' => 'image_gen', 'model_name' => 'gemini-3-flash-image', 'display_name' => 'Gemini 3.1 Flash Image',
                'provider' => 'Google', 'api_type' => 'gemini',
                'base_url' => 'https://generativelanguage.googleapis.com', 'request_path' => '/v1beta/models/gemini-2.0-flash-exp:generateContent',
                'default_params' => json_encode([]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => 'Gemini原生图像生成',
                'sort_order' => 3,
            ],
            [
                'category' => 'image_gen', 'model_name' => 'imagen-3', 'display_name' => 'Imagen 3',
                'provider' => 'Google', 'api_type' => 'gemini',
                'base_url' => 'https://generativelanguage.googleapis.com', 'request_path' => '/v1beta/models/imagen-3.0:generateContent',
                'default_params' => json_encode(['sample_count' => 1]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => 'Google Imagen 3 图像生成',
                'sort_order' => 4,
            ],
            [
                'category' => 'image_gen', 'model_name' => 'qwen-image', 'display_name' => '通义万相 Qwen Image',
                'provider' => '阿里云', 'api_type' => 'openai',
                'base_url' => 'https://dashscope.aliyuncs.com/compatible-mode', 'request_path' => '/v1/images/generations',
                'default_params' => json_encode(['model' => 'qwen-image', 'n' => 1]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '阿里云百炼，OpenAI兼容图片接口',
                'sort_order' => 5,
            ],
            [
                'category' => 'image_gen', 'model_name' => 'sd-3.5', 'display_name' => 'Stable Diffusion 3.5',
                'provider' => 'Stability AI', 'api_type' => 'stability',
                'base_url' => 'https://api.stability.ai', 'request_path' => '/v2beta/stable-image/generate/sd3',
                'default_params' => json_encode(['output_format' => 'png']),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => 'Stability AI 官方API',
                'sort_order' => 6,
            ],
            [
                'category' => 'image_gen', 'model_name' => 'flux.1-pro', 'display_name' => 'Flux.1 Pro',
                'provider' => 'Black Forest Labs', 'api_type' => 'bfl',
                'base_url' => 'https://api.bfl.ml', 'request_path' => '/v1/flux-pro-1.1',
                'default_params' => json_encode(['width' => 1920, 'height' => 1080, 'steps' => 30]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => 'BFL官方API，x-key Header认证',
                'sort_order' => 7,
            ],
            [
                'category' => 'image_gen', 'model_name' => 'flux.1-replicate', 'display_name' => 'Flux.1 (Replicate)',
                'provider' => 'Black Forest Labs via Replicate', 'api_type' => 'replicate',
                'base_url' => 'https://api.replicate.com', 'request_path' => '/v1/models/black-forest-labs/flux-pro/predictions',
                'default_params' => json_encode(['num_outputs' => 1]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '通过Replicate托管调用Flux',
                'sort_order' => 8,
            ],
            [
                'category' => 'image_gen', 'model_name' => 'doubao-image', 'display_name' => '豆包图像',
                'provider' => '字节跳动', 'api_type' => 'openai',
                'base_url' => 'https://ark.cn-beijing.volces.com', 'request_path' => '/api/v3/images/generations',
                'default_params' => json_encode(['model' => 'doubao-image', 'n' => 1]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '火山引擎，需创建Endpoint',
                'sort_order' => 9,
            ],
            [
                'category' => 'image_gen', 'model_name' => 'ernie-vilg', 'display_name' => '文心一格 ERNIE-ViLG',
                'provider' => '百度', 'api_type' => 'openai',
                'base_url' => 'https://qianfan.baidubce.com', 'request_path' => '/v2/images/generations',
                'default_params' => json_encode(['model' => 'ernie-vilg', 'width' => 1920, 'height' => 1080]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '百度千帆V2图像生成',
                'sort_order' => 10,
            ],
            [
                'category' => 'image_gen', 'model_name' => 'cogview-4', 'display_name' => 'CogView-4',
                'provider' => '智谱 Zhipu', 'api_type' => 'openai',
                'base_url' => 'https://open.bigmodel.cn', 'request_path' => '/api/paas/v4/images/generations',
                'default_params' => json_encode(['model' => 'cogview-4', 'n' => 1]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '智谱 OpenAI兼容图片接口',
                'sort_order' => 11,
            ],
            [
                'category' => 'image_gen', 'model_name' => 'ideogram-3.0', 'display_name' => 'Ideogram 3.0',
                'provider' => 'Ideogram', 'api_type' => 'custom',
                'base_url' => 'https://api.ideogram.ai', 'request_path' => '/generate',
                'default_params' => json_encode(['style_type' => 'general']),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => 'Api-Key Header认证',
                'sort_order' => 12,
            ],
            [
                'category' => 'image_gen', 'model_name' => 'recraft-v3', 'display_name' => 'Recraft V3',
                'provider' => 'Recraft', 'api_type' => 'custom',
                'base_url' => 'https://external.api.recraft.ai', 'request_path' => '/v1/images/generations',
                'default_params' => json_encode(['style' => 'digital_illustration']),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '支持多种风格预设',
                'sort_order' => 13,
            ],
            [
                'category' => 'image_gen', 'model_name' => 'minimax-image-01', 'display_name' => 'MiniMax Image-01',
                'provider' => 'MiniMax 稀宇', 'api_type' => 'custom',
                'base_url' => 'https://api.minimax.io', 'request_path' => '/v1/image/generation',
                'default_params' => json_encode(['n' => 1]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => 'MiniMax自有API [端点待确认]',
                'sort_order' => 14,
            ],
            [
                'category' => 'image_gen', 'model_name' => 'seedream-4', 'display_name' => 'Seedream 4',
                'provider' => '字节跳动', 'api_type' => 'openai',
                'base_url' => 'https://ark.cn-beijing.volces.com', 'request_path' => '/api/v3/images/generations',
                'default_params' => json_encode(['model' => 'seedream-4', 'n' => 1]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '火山引擎 [端点待确认]',
                'sort_order' => 15,
            ],
        ];
    }

    // ─── Category 3: Consistency (环节5) ────────────────────────
    private function consistencyModels(): array
    {
        return [
            [
                'category' => 'consistency', 'model_name' => 'controlnet-reference', 'display_name' => 'ControlNet (Reference)',
                'provider' => '开源', 'api_type' => 'replicate',
                'base_url' => 'https://api.replicate.com', 'request_path' => '/v1/models/rossjillian/controlnet/predictions',
                'default_params' => json_encode([]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '基于参考图约束SD生成。Replicate托管',
                'sort_order' => 1,
            ],
            [
                'category' => 'consistency', 'model_name' => 'instantid', 'display_name' => 'InstantID',
                'provider' => '开源', 'api_type' => 'replicate',
                'base_url' => 'https://api.replicate.com', 'request_path' => '/v1/models/zsxkib/instantid/predictions',
                'default_params' => json_encode([]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '单图人脸一致性。输入人脸图+提示词',
                'sort_order' => 2,
            ],
            [
                'category' => 'consistency', 'model_name' => 'photomaker-v2', 'display_name' => 'PhotoMaker V2',
                'provider' => '开源', 'api_type' => 'replicate',
                'base_url' => 'https://api.replicate.com', 'request_path' => '/v1/models/tencentarc/photomaker-v2/predictions',
                'default_params' => json_encode([]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '多图角色风格化',
                'sort_order' => 3,
            ],
            [
                'category' => 'consistency', 'model_name' => 'ip-adapter-faceid', 'display_name' => 'IP-Adapter FaceID',
                'provider' => '开源', 'api_type' => 'replicate',
                'base_url' => 'https://api.replicate.com', 'request_path' => '/v1/models/zsxkib/ip-adapter-faceid/predictions',
                'default_params' => json_encode([]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '人脸特征精准注入',
                'sort_order' => 4,
            ],
            [
                'category' => 'consistency', 'model_name' => 'lora-training-aliyun', 'display_name' => 'LoRA训练 (阿里云PAI)',
                'provider' => '阿里云', 'api_type' => 'custom',
                'base_url' => 'https://pai.aliyuncs.com', 'request_path' => null,
                'default_params' => json_encode([]),
                'required_fields' => json_encode([
                    ['key' => 'access_key', 'label' => 'Access Key ID', 'type' => 'password'],
                    ['key' => 'access_secret', 'label' => 'Access Key Secret', 'type' => 'password'],
                ]),
                'description' => '阿里云机器学习平台，训练专属角色LoRA [端点待确认]',
                'sort_order' => 5,
            ],
            [
                'category' => 'consistency', 'model_name' => 'lora-training-volcano', 'display_name' => 'LoRA训练 (火山引擎)',
                'provider' => '字节跳动', 'api_type' => 'custom',
                'base_url' => 'https://ark.cn-beijing.volces.com', 'request_path' => null,
                'default_params' => json_encode([]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '火山引擎模型训练API [端点待确认]',
                'sort_order' => 6,
            ],
        ];
    }

    // ─── Category 4: Image Enhance (环节6) ──────────────────────
    private function imageEnhanceModels(): array
    {
        return [
            [
                'category' => 'image_enhance', 'model_name' => 'aliyun-image-enhance', 'display_name' => '阿里云图像增强',
                'provider' => '阿里云', 'api_type' => 'custom',
                'base_url' => 'https://vision.aliyuncs.com', 'request_path' => null,
                'default_params' => json_encode([]),
                'required_fields' => json_encode([
                    ['key' => 'access_key', 'label' => 'Access Key ID', 'type' => 'password'],
                    ['key' => 'access_secret', 'label' => 'Access Key Secret', 'type' => 'password'],
                ]),
                'description' => '超分/降噪/调色。阿里云视觉智能API [端点待确认]',
                'sort_order' => 1,
            ],
            [
                'category' => 'image_enhance', 'model_name' => 'tencent-image-enhance', 'display_name' => '腾讯云图像增强',
                'provider' => '腾讯云', 'api_type' => 'custom',
                'base_url' => 'https://tiia.tencentcloudapi.com', 'request_path' => null,
                'default_params' => json_encode([]),
                'required_fields' => json_encode([
                    ['key' => 'secret_id', 'label' => 'Secret Id', 'type' => 'password'],
                    ['key' => 'secret_key', 'label' => 'Secret Key', 'type' => 'password'],
                ]),
                'description' => '超分/降噪/调色。腾讯云图像分析API [端点待确认]',
                'sort_order' => 2,
            ],
            [
                'category' => 'image_enhance', 'model_name' => 'real-esrgan', 'display_name' => 'Real-ESRGAN',
                'provider' => '开源', 'api_type' => 'replicate',
                'base_url' => 'https://api.replicate.com', 'request_path' => '/v1/models/nightmareai/real-esrgan/predictions',
                'default_params' => json_encode(['scale' => 4]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '4x超分辨率。Replicate托管',
                'sort_order' => 3,
            ],
            [
                'category' => 'image_enhance', 'model_name' => 'gfpgan', 'display_name' => 'GFPGAN',
                'provider' => '开源', 'api_type' => 'replicate',
                'base_url' => 'https://api.replicate.com', 'request_path' => '/v1/models/tencentarc/gfpgan/predictions',
                'default_params' => json_encode([]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '人脸修复',
                'sort_order' => 4,
            ],
            [
                'category' => 'image_enhance', 'model_name' => 'codeformer', 'display_name' => 'CodeFormer',
                'provider' => '开源', 'api_type' => 'replicate',
                'base_url' => 'https://api.replicate.com', 'request_path' => '/v1/models/sczhou/codeformer/predictions',
                'default_params' => json_encode(['fidelity' => 0.7]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '人脸复原+增强',
                'sort_order' => 5,
            ],
        ];
    }

    // ─── Category 5: Image2Video (环节7) ────────────────────────
    private function image2VideoModels(): array
    {
        return [
            [
                'category' => 'image2video', 'model_name' => 'kling-v3-omni', 'display_name' => '可灵 V3 Omni',
                'provider' => '快手 Kling', 'api_type' => 'kling',
                'base_url' => 'https://api-beijing.klingai.com', 'request_path' => '/v1/videos/image2video',
                'default_params' => json_encode(['duration' => 8, 'cfg_scale' => 0.5]),
                'required_fields' => json_encode([
                    ['key' => 'access_key', 'label' => 'Access Key ID', 'type' => 'password'],
                    ['key' => 'access_secret', 'label' => 'Access Key Secret', 'type' => 'password'],
                ]),
                'description' => 'JWT HS256签名。异步API（提交→轮询结果）。支持图片+文本双重输入',
                'sort_order' => 1,
            ],
            [
                'category' => 'image2video', 'model_name' => 'sora-2', 'display_name' => 'Sora-2 (⚠️2026.9关停)',
                'provider' => 'OpenAI', 'api_type' => 'openai',
                'base_url' => 'https://api.openai.com', 'request_path' => '/v1/videos',
                'default_params' => json_encode(['model' => 'sora-2']),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '⚠️ OpenAI已宣布2026.9.24关停API。不推荐新项目接入。替代: SeeDance 2.0/Wan 2.7/可灵3.0/Veo 3.1/Runway Gen-4.5',
                'status' => 'inactive', 'sort_order' => 20,
            ],
            [
                'category' => 'image2video', 'model_name' => 'runway-gen-4.5', 'display_name' => 'Runway Gen-4.5',
                'provider' => 'Runway', 'api_type' => 'custom',
                'base_url' => 'https://api.runwayml.com', 'request_path' => '/v1/image_to_video',
                'default_params' => json_encode(['duration' => 8, 'motion_bucket_id' => 100]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => 'Gen-4已由Gen-4.5 2026.2取代。motion_bucket控制运动幅度',
                'sort_order' => 2,
            ],
            [
                'category' => 'image2video', 'model_name' => 'doubao-video', 'display_name' => '豆包视频',
                'provider' => '字节跳动', 'api_type' => 'custom',
                'base_url' => 'https://ark.cn-beijing.volces.com', 'request_path' => null,
                'default_params' => json_encode(['duration' => 8, 'fps' => 24]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '火山引擎视频生成Endpoint [路径待确认]',
                'sort_order' => 3,
            ],
            [
                'category' => 'image2video', 'model_name' => 'qwen-video', 'display_name' => '通义万相视频',
                'provider' => '阿里云', 'api_type' => 'custom',
                'base_url' => 'https://dashscope.aliyuncs.com', 'request_path' => null,
                'default_params' => json_encode(['duration' => 8]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '阿里云百炼视频生成 [端点待确认]',
                'sort_order' => 4,
            ],
            [
                'category' => 'image2video', 'model_name' => 'minimax-video-01', 'display_name' => 'MiniMax Video-01',
                'provider' => 'MiniMax 稀宇', 'api_type' => 'custom',
                'base_url' => 'https://api.minimax.io', 'request_path' => '/v1/video_generation',
                'default_params' => json_encode(['duration' => 8]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => 'MiniMax自有API [端点待确认]',
                'sort_order' => 5,
            ],
            [
                'category' => 'image2video', 'model_name' => 'vidu', 'display_name' => 'Vidu',
                'provider' => '生数科技', 'api_type' => 'custom',
                'base_url' => 'https://api.vidu.com', 'request_path' => null,
                'default_params' => json_encode(['duration' => 8, 'style' => 'cinematic']),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => 'Vidu API [端点待确认]',
                'sort_order' => 6,
            ],
            [
                'category' => 'image2video', 'model_name' => 'luma-dream-machine', 'display_name' => 'Luma Dream Machine',
                'provider' => 'Luma AI', 'api_type' => 'custom',
                'base_url' => 'https://api.lumalabs.ai', 'request_path' => '/dream-machine/v1/generations',
                'default_params' => json_encode(['aspect_ratio' => '16:9']),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => 'Luma AI异步API',
                'sort_order' => 7,
            ],
            [
                'category' => 'image2video', 'model_name' => 'haiper-2.0', 'display_name' => 'Haiper 2.0',
                'provider' => 'Haiper', 'api_type' => 'custom',
                'base_url' => 'https://api.haiper.ai', 'request_path' => null,
                'default_params' => json_encode(['duration' => 8]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => 'Haiper API [端点待确认]',
                'sort_order' => 8,
            ],
            [
                'category' => 'image2video', 'model_name' => 'krea-video', 'display_name' => 'Krea Video',
                'provider' => 'Krea AI', 'api_type' => 'custom',
                'base_url' => 'https://api.krea.ai', 'request_path' => null,
                'default_params' => json_encode([]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => 'Krea API [端点待确认]',
                'sort_order' => 9,
            ],
            [
                'category' => 'image2video', 'model_name' => 'cogvideo', 'display_name' => 'CogVideo',
                'provider' => '智谱 Zhipu', 'api_type' => 'openai',
                'base_url' => 'https://open.bigmodel.cn', 'request_path' => null,
                'default_params' => json_encode(['fps' => 24]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '智谱视频生成 [端点待确认]',
                'sort_order' => 10,
            ],
            [
                'category' => 'image2video', 'model_name' => 'veo-3.1', 'display_name' => 'Veo 3.1',
                'provider' => 'Google', 'api_type' => 'gemini',
                'base_url' => 'https://generativelanguage.googleapis.com', 'request_path' => null,
                'default_params' => json_encode(['number_of_frames' => 192, 'fps' => 24]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => 'Veo 3已由Veo 3.1取代，新增原生音频/4K。Vertex AI [端点待确认]',
                'sort_order' => 11,
            ],
            [
                'category' => 'image2video', 'model_name' => 'mochi-1', 'display_name' => 'Mochi 1',
                'provider' => 'Genmo', 'api_type' => 'replicate',
                'base_url' => 'https://api.replicate.com', 'request_path' => '/v1/models/genmo/mochi-1/predictions',
                'default_params' => json_encode(['num_frames' => 49]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => 'Replicate托管',
                'sort_order' => 12,
            ],
            [
                'category' => 'image2video', 'model_name' => 'svd', 'display_name' => 'Stable Video Diffusion',
                'provider' => 'Stability AI', 'api_type' => 'replicate',
                'base_url' => 'https://api.replicate.com', 'request_path' => '/v1/models/stability-ai/stable-video-diffusion/predictions',
                'default_params' => json_encode(['motion_bucket_id' => 100, 'fps' => 24]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => 'Replicate托管',
                'sort_order' => 13,
            ],
        ];
    }

    // ─── Category 6: Video Enhance (环节8) ──────────────────────
    private function videoEnhanceModels(): array
    {
        return [
            [
                'category' => 'video_enhance', 'model_name' => 'aliyun-video-enhance', 'display_name' => '阿里云视频增强',
                'provider' => '阿里云', 'api_type' => 'custom',
                'base_url' => 'https://mps.aliyuncs.com', 'request_path' => null,
                'default_params' => json_encode([]),
                'required_fields' => json_encode([
                    ['key' => 'access_key', 'label' => 'Access Key ID', 'type' => 'password'],
                    ['key' => 'access_secret', 'label' => 'Access Key Secret', 'type' => 'password'],
                ]),
                'description' => '超分/补帧/降噪。阿里云媒体处理 [端点待确认]',
                'sort_order' => 1,
            ],
            [
                'category' => 'video_enhance', 'model_name' => 'tencent-video-enhance', 'display_name' => '腾讯云媒体处理',
                'provider' => '腾讯云', 'api_type' => 'custom',
                'base_url' => 'https://mps.tencentcloudapi.com', 'request_path' => null,
                'default_params' => json_encode([]),
                'required_fields' => json_encode([
                    ['key' => 'secret_id', 'label' => 'Secret Id', 'type' => 'password'],
                    ['key' => 'secret_key', 'label' => 'Secret Key', 'type' => 'password'],
                ]),
                'description' => '超分/补帧/降噪/调色。腾讯云媒体处理服务 [端点待确认]',
                'sort_order' => 2,
            ],
            [
                'category' => 'video_enhance', 'model_name' => 'rife', 'display_name' => 'RIFE (AI帧插值)',
                'provider' => '开源', 'api_type' => 'replicate',
                'base_url' => 'https://api.replicate.com', 'request_path' => '/v1/models/lucataco/rife-esrgan/predictions',
                'default_params' => json_encode(['factor' => 2]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => 'AI帧插值(补帧)，Replicate托管',
                'sort_order' => 3,
            ],
            [
                'category' => 'video_enhance', 'model_name' => 'ffmpeg-lut', 'display_name' => 'FFmpeg + LUT调色',
                'provider' => '自研', 'api_type' => 'custom',
                'base_url' => 'internal', 'request_path' => null,
                'default_params' => json_encode(['lut_preset' => 'rec709']),
                'required_fields' => json_encode([]),
                'description' => '基础调色（非AI，兜底方案）。服务端FFmpeg调用',
                'sort_order' => 99,
            ],
        ];
    }

    // ─── Category 7: TTS (环节9) ─────────────────────────────────
    private function ttsModels(): array
    {
        return [
            [
                'category' => 'tts', 'model_name' => 'kling-tts', 'display_name' => '可灵 V3 Omni (TTS)',
                'provider' => '快手 Kling', 'api_type' => 'kling',
                'base_url' => 'https://api-beijing.klingai.com', 'request_path' => '/v1/audio/tts',
                'default_params' => json_encode(['speed' => 1.0, 'emotion' => 'neutral']),
                'required_fields' => json_encode([
                    ['key' => 'access_key', 'label' => 'Access Key ID', 'type' => 'password'],
                    ['key' => 'access_secret', 'label' => 'Access Key Secret', 'type' => 'password'],
                ]),
                'description' => '多音色、情感合成。JWT HS256签名',
                'sort_order' => 1,
            ],
            [
                'category' => 'tts', 'model_name' => 'elevenlabs', 'display_name' => 'ElevenLabs',
                'provider' => 'ElevenLabs', 'api_type' => 'elevenlabs',
                'base_url' => 'https://api.elevenlabs.io', 'request_path' => '/v1/text-to-speech/{voice_id}',
                'default_params' => json_encode(['model_id' => 'eleven_multilingual_v2', 'stability' => 0.5, 'similarity_boost' => 0.75]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '高表现力、音色克隆。xi-api-key Header',
                'sort_order' => 2,
            ],
            [
                'category' => 'tts', 'model_name' => 'openai-tts', 'display_name' => 'OpenAI TTS',
                'provider' => 'OpenAI', 'api_type' => 'openai',
                'base_url' => 'https://api.openai.com', 'request_path' => '/v1/audio/speech',
                'default_params' => json_encode(['model' => 'tts-1-hd', 'voice' => 'alloy', 'speed' => 1.0, 'response_format' => 'mp3']),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '自然语音，OpenAI Audio API',
                'sort_order' => 3,
            ],
            [
                'category' => 'tts', 'model_name' => 'doubao-tts', 'display_name' => '豆包语音 TTS',
                'provider' => '字节跳动', 'api_type' => 'custom',
                'base_url' => 'https://ark.cn-beijing.volces.com', 'request_path' => null,
                'default_params' => json_encode(['speed' => 1.0, 'volume' => 1.0]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '中文优化、多音色。火山引擎 [端点待确认]',
                'sort_order' => 4,
            ],
            [
                'category' => 'tts', 'model_name' => 'aliyun-tts', 'display_name' => '阿里云语音合成',
                'provider' => '阿里云', 'api_type' => 'custom',
                'base_url' => 'https://nls-gateway.aliyuncs.com', 'request_path' => null,
                'default_params' => json_encode(['format' => 'mp3', 'sample_rate' => 16000]),
                'required_fields' => json_encode([
                    ['key' => 'access_key', 'label' => 'Access Key ID', 'type' => 'password'],
                    ['key' => 'access_secret', 'label' => 'Access Key Secret', 'type' => 'password'],
                ]),
                'description' => '中文优化、场景音色。阿里云AK/SK签名',
                'sort_order' => 5,
            ],
            [
                'category' => 'tts', 'model_name' => 'tencent-tts', 'display_name' => '腾讯云语音合成',
                'provider' => '腾讯云', 'api_type' => 'custom',
                'base_url' => 'https://tts.tencentcloudapi.com', 'request_path' => null,
                'default_params' => json_encode(['speed' => 1.0, 'volume' => 1.0]),
                'required_fields' => json_encode([
                    ['key' => 'secret_id', 'label' => 'Secret Id', 'type' => 'password'],
                    ['key' => 'secret_key', 'label' => 'Secret Key', 'type' => 'password'],
                ]),
                'description' => '中文优化。腾讯云AK/SK签名',
                'sort_order' => 6,
            ],
            [
                'category' => 'tts', 'model_name' => 'baidu-tts', 'display_name' => '百度语音合成',
                'provider' => '百度', 'api_type' => 'custom',
                'base_url' => 'https://tsn.baidu.com', 'request_path' => '/text2audio',
                'default_params' => json_encode(['ctp' => 1, 'lan' => 'zh', 'spd' => 5, 'pit' => 5, 'vol' => 5, 'per' => 0]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key (access_token)', 'type' => 'password']]),
                'description' => '中文优化。通过tok参数传递access_token',
                'sort_order' => 7,
            ],
            [
                'category' => 'tts', 'model_name' => 'iflytek-tts', 'display_name' => '讯飞语音合成',
                'provider' => '科大讯飞', 'api_type' => 'custom',
                'base_url' => 'https://api.xfyun.cn', 'request_path' => '/v1/service/v1/tts',
                'default_params' => json_encode(['voice_name' => 'xiaoyan', 'speed' => 50, 'volume' => 50]),
                'required_fields' => json_encode([
                    ['key' => 'app_id', 'label' => 'App ID', 'type' => 'password'],
                    ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password'],
                    ['key' => 'api_secret', 'label' => 'API Secret', 'type' => 'password'],
                ]),
                'description' => '中文最强、方言支持。多重Header签名（X-Appid + X-CurTime + X-Param + X-CheckSum）',
                'sort_order' => 8,
            ],
            [
                'category' => 'tts', 'model_name' => 'minimax-tts', 'display_name' => 'MiniMax TTS',
                'provider' => 'MiniMax 稀宇', 'api_type' => 'custom',
                'base_url' => 'https://api.minimax.io', 'request_path' => '/v1/t2a_v2',
                'default_params' => json_encode(['speed' => 1.0, 'vol' => 1.0]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '情感丰富 [端点待确认]',
                'sort_order' => 9,
            ],
            [
                'category' => 'tts', 'model_name' => 'azure-speech', 'display_name' => 'Azure Speech TTS',
                'provider' => 'Microsoft', 'api_type' => 'azure',
                'base_url' => 'https://{region}.tts.speech.microsoft.com', 'request_path' => '/cognitiveservices/v1',
                'default_params' => json_encode([]),
                'required_fields' => json_encode([
                    ['key' => 'api_key', 'label' => 'Subscription Key', 'type' => 'password'],
                    ['key' => 'region', 'label' => 'Region', 'type' => 'text'],
                ]),
                'description' => '多语言、SSML支持。Ocp-Apim-Subscription-Key Header',
                'sort_order' => 10,
            ],
            [
                'category' => 'tts', 'model_name' => 'google-cloud-tts', 'display_name' => 'Google Cloud TTS',
                'provider' => 'Google', 'api_type' => 'gemini',
                'base_url' => 'https://texttospeech.googleapis.com', 'request_path' => '/v1/text:synthesize',
                'default_params' => json_encode([]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '多语言Chirp 3。x-goog-api-key或OAuth',
                'sort_order' => 11,
            ],
            [
                'category' => 'tts', 'model_name' => 'fish-audio', 'display_name' => 'Fish Audio',
                'provider' => 'Fish Audio', 'api_type' => 'custom',
                'base_url' => 'https://api.fish.audio', 'request_path' => '/v1/tts',
                'default_params' => json_encode(['format' => 'mp3']),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '开源、音色克隆。Bearer Token认证',
                'sort_order' => 12,
            ],
        ];
    }

    // ─── Category 8: Music (环节10) ──────────────────────────────
    private function musicModels(): array
    {
        return [
            [
                'category' => 'music', 'model_name' => 'kling-sound-effects', 'display_name' => '可灵 V3 (音效)',
                'provider' => '快手 Kling', 'api_type' => 'kling',
                'base_url' => 'https://api-beijing.klingai.com', 'request_path' => '/v1/audio/text-to-audio',
                'default_params' => json_encode(['duration' => 30]),
                'required_fields' => json_encode([
                    ['key' => 'access_key', 'label' => 'Access Key ID', 'type' => 'password'],
                    ['key' => 'access_secret', 'label' => 'Access Key Secret', 'type' => 'password'],
                ]),
                'description' => '⚠️ 仅音效生成，非完整音乐。JWT签名',
                'sort_order' => 1,
            ],
            [
                'category' => 'music', 'model_name' => 'suno', 'display_name' => 'Suno (⚠️无公开API)',
                'provider' => 'Suno', 'api_type' => 'custom',
                'base_url' => 'https://api.suno.ai', 'request_path' => null,
                'default_params' => json_encode(['instrumental' => true]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '⚠️ 无自助API，需企业合作。不推荐接入 [端点待确认]',
                'status' => 'inactive', 'sort_order' => 99,
            ],
            [
                'category' => 'music', 'model_name' => 'udio', 'display_name' => 'Udio',
                'provider' => 'Udio', 'api_type' => 'custom',
                'base_url' => 'https://api.udio.com', 'request_path' => null,
                'default_params' => json_encode(['duration' => 30, 'genre' => 'cinematic']),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => 'Udio API [端点待确认]',
                'sort_order' => 2,
            ],
            [
                'category' => 'music', 'model_name' => 'stable-audio-2', 'display_name' => 'Stable Audio 2',
                'provider' => 'Stability AI', 'api_type' => 'stability',
                'base_url' => 'https://api.stability.ai', 'request_path' => '/v2beta/audio/stable-audio-2/generate',
                'default_params' => json_encode(['duration' => 30, 'steps' => 100]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '⚠️ 使用multipart/form-data非JSON',
                'sort_order' => 3,
            ],
            [
                'category' => 'music', 'model_name' => 'musicgen', 'display_name' => 'MusicGen',
                'provider' => 'Meta (开源)', 'api_type' => 'replicate',
                'base_url' => 'https://api.replicate.com', 'request_path' => '/v1/models/meta/musicgen/predictions',
                'default_params' => json_encode(['duration' => 30, 'temperature' => 1.0]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '开源音乐生成，Replicate托管',
                'sort_order' => 4,
            ],
            [
                'category' => 'music', 'model_name' => 'skymusic', 'display_name' => '天工音乐 SkyMusic',
                'provider' => '昆仑万维', 'api_type' => 'custom',
                'base_url' => 'https://api.tiangong.cn', 'request_path' => null,
                'default_params' => json_encode(['duration' => 30]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '天工 API [端点待确认]',
                'sort_order' => 5,
            ],
        ];
    }

    // ─── Category 9: ASR (环节11) ────────────────────────────────
    private function asrModels(): array
    {
        return [
            [
                'category' => 'asr', 'model_name' => 'whisper-openai', 'display_name' => 'Whisper (OpenAI)',
                'provider' => 'OpenAI', 'api_type' => 'openai',
                'base_url' => 'https://api.openai.com', 'request_path' => '/v1/audio/transcriptions',
                'default_params' => json_encode(['model' => 'whisper-1', 'language' => 'zh', 'response_format' => 'verbose_json']),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => 'OpenAI托管Whisper。multipart/form-data上传音频',
                'sort_order' => 1,
            ],
            [
                'category' => 'asr', 'model_name' => 'aliyun-asr', 'display_name' => '阿里云语音识别',
                'provider' => '阿里云', 'api_type' => 'custom',
                'base_url' => 'https://nls-gateway.aliyuncs.com', 'request_path' => null,
                'default_params' => json_encode(['format' => 'wav', 'sample_rate' => 16000]),
                'required_fields' => json_encode([
                    ['key' => 'access_key', 'label' => 'Access Key ID', 'type' => 'password'],
                    ['key' => 'access_secret', 'label' => 'Access Key Secret', 'type' => 'password'],
                ]),
                'description' => '阿里云智能语音交互。AK/SK签名',
                'sort_order' => 2,
            ],
            [
                'category' => 'asr', 'model_name' => 'tencent-asr', 'display_name' => '腾讯云语音识别',
                'provider' => '腾讯云', 'api_type' => 'custom',
                'base_url' => 'https://asr.tencentcloudapi.com', 'request_path' => null,
                'default_params' => json_encode(['engine_type' => '16k_zh']),
                'required_fields' => json_encode([
                    ['key' => 'secret_id', 'label' => 'Secret Id', 'type' => 'password'],
                    ['key' => 'secret_key', 'label' => 'Secret Key', 'type' => 'password'],
                ]),
                'description' => '腾讯云ASR。AK/SK签名',
                'sort_order' => 3,
            ],
            [
                'category' => 'asr', 'model_name' => 'baidu-asr', 'display_name' => '百度语音识别',
                'provider' => '百度', 'api_type' => 'custom',
                'base_url' => 'https://vop.baidu.com', 'request_path' => '/server_api',
                'default_params' => json_encode(['format' => 'wav', 'rate' => 16000, 'lan' => 'zh']),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key (access_token)', 'type' => 'password']]),
                'description' => '百度AI开放平台。Query参数签名',
                'sort_order' => 4,
            ],
            [
                'category' => 'asr', 'model_name' => 'iflytek-asr', 'display_name' => '讯飞语音转写',
                'provider' => '科大讯飞', 'api_type' => 'custom',
                'base_url' => 'https://api.xfyun.cn', 'request_path' => '/v1/service/v1/iat',
                'default_params' => json_encode(['language' => 'zh_cn', 'accent' => 'mandarin']),
                'required_fields' => json_encode([
                    ['key' => 'app_id', 'label' => 'App ID', 'type' => 'password'],
                    ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password'],
                    ['key' => 'api_secret', 'label' => 'API Secret', 'type' => 'password'],
                ]),
                'description' => '中文识别最准。Header签名（同讯飞TTS）',
                'sort_order' => 5,
            ],
            [
                'category' => 'asr', 'model_name' => 'deepgram', 'display_name' => 'Deepgram',
                'provider' => 'Deepgram', 'api_type' => 'custom',
                'base_url' => 'https://api.deepgram.com', 'request_path' => '/v1/listen',
                'default_params' => json_encode(['model' => 'nova-2', 'language' => 'zh-CN']),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => '实时语音识别',
                'sort_order' => 6,
            ],
            [
                'category' => 'asr', 'model_name' => 'google-cloud-stt', 'display_name' => 'Google Cloud STT',
                'provider' => 'Google', 'api_type' => 'gemini',
                'base_url' => 'https://speech.googleapis.com', 'request_path' => '/v1/speech:recognize',
                'default_params' => json_encode([]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => 'Chirp 3。x-goog-api-key或OAuth',
                'sort_order' => 7,
            ],
            [
                'category' => 'asr', 'model_name' => 'azure-stt', 'display_name' => 'Azure Speech-to-Text',
                'provider' => 'Microsoft', 'api_type' => 'azure',
                'base_url' => 'https://{region}.stt.speech.microsoft.com', 'request_path' => '/speech/recognition/conversation/cognitiveservices/v1',
                'default_params' => json_encode([]),
                'required_fields' => json_encode([
                    ['key' => 'api_key', 'label' => 'Subscription Key', 'type' => 'password'],
                    ['key' => 'region', 'label' => 'Region', 'type' => 'text'],
                ]),
                'description' => '多语言识别。Ocp-Apim-Subscription-Key Header',
                'sort_order' => 8,
            ],
        ];
    }

    // ─── Category 10: Moderation (环节12) ────────────────────────
    private function moderationModels(): array
    {
        return [
            [
                'category' => 'moderation', 'model_name' => 'aliyun-content-safety', 'display_name' => '阿里云内容安全',
                'provider' => '阿里云', 'api_type' => 'custom',
                'base_url' => 'https://green.aliyuncs.com', 'request_path' => null,
                'default_params' => json_encode(['scenes' => ['porn', 'terrorism', 'ad', 'qrcode']]),
                'required_fields' => json_encode([
                    ['key' => 'access_key', 'label' => 'Access Key ID', 'type' => 'password'],
                    ['key' => 'access_secret', 'label' => 'Access Key Secret', 'type' => 'password'],
                ]),
                'description' => '阿里云内容安全API。AK/SK签名',
                'sort_order' => 1,
            ],
            [
                'category' => 'moderation', 'model_name' => 'tencent-cms', 'display_name' => '腾讯云天御',
                'provider' => '腾讯云', 'api_type' => 'custom',
                'base_url' => 'https://cms.tencentcloudapi.com', 'request_path' => null,
                'default_params' => json_encode([]),
                'required_fields' => json_encode([
                    ['key' => 'secret_id', 'label' => 'Secret Id', 'type' => 'password'],
                    ['key' => 'secret_key', 'label' => 'Secret Key', 'type' => 'password'],
                ]),
                'description' => '腾讯云内容安全。AK/SK签名',
                'sort_order' => 2,
            ],
            [
                'category' => 'moderation', 'model_name' => 'baidu-moderation', 'display_name' => '百度内容审核',
                'provider' => '百度', 'api_type' => 'custom',
                'base_url' => 'https://aip.baidubce.com', 'request_path' => '/rest/2.0/solution/v1/text_censor/v2/user_defined',
                'default_params' => json_encode([]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key (access_token)', 'type' => 'password']]),
                'description' => '百度AI开放平台。access_token通过Query传递',
                'sort_order' => 3,
            ],
            [
                'category' => 'moderation', 'model_name' => 'openai-moderation', 'display_name' => 'OpenAI Moderation',
                'provider' => 'OpenAI', 'api_type' => 'openai',
                'base_url' => 'https://api.openai.com', 'request_path' => '/v1/moderations',
                'default_params' => json_encode(['model' => 'omni-moderation-latest']),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => 'OpenAI内容审核API',
                'sort_order' => 4,
            ],
            [
                'category' => 'moderation', 'model_name' => 'google-moderation', 'display_name' => 'Google Cloud Moderation',
                'provider' => 'Google', 'api_type' => 'custom',
                'base_url' => 'https://language.googleapis.com', 'request_path' => null,
                'default_params' => json_encode([]),
                'required_fields' => json_encode([['key' => 'api_key', 'label' => 'API Key', 'type' => 'password']]),
                'description' => 'Google Cloud NLP。x-goog-api-key [端点待确认]',
                'sort_order' => 5,
            ],
        ];
    }
}
