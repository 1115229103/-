<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActionTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $actions = [
            // 推 (Push)
            ['name' => '缓推镜头', 'category' => '推', 'prompt_cn' => '镜头缓慢向前推进，画面主体逐渐放大，景深由深变浅', 'prompt_en' => 'slow push-in, subject gradually enlarges, depth of field shallow', 'tags' => json_encode(['推', '缓慢', '强调'])],
            ['name' => '快速推进', 'category' => '推', 'prompt_cn' => '镜头快速向前推进，产生强烈的视觉冲击力，画面主体急剧放大', 'prompt_en' => 'fast push-in, strong visual impact, subject suddenly enlarges', 'tags' => json_encode(['推', '快速', '冲击'])],
            // 拉 (Pull)
            ['name' => '缓拉镜头', 'category' => '拉', 'prompt_cn' => '镜头缓慢向后拉远，画面视野逐渐扩大，展现更广阔的环境', 'prompt_en' => 'slow pull-out, gradually revealing wider environment', 'tags' => json_encode(['拉', '缓慢', '环境'])],
            ['name' => '快速拉远', 'category' => '拉', 'prompt_cn' => '镜头快速拉远，瞬间展现全景，人物在画面中变小', 'prompt_en' => 'fast pull-out, instantly revealing full scene', 'tags' => json_encode(['拉', '快速', '全景'])],
            // 摇 (Pan)
            ['name' => '水平摇镜(左→右)', 'category' => '摇', 'prompt_cn' => '镜头从左向右水平摇动，跟随主体移动或展示场景', 'prompt_en' => 'pan from left to right, following subject or revealing scene', 'tags' => json_encode(['摇', '水平', '跟随'])],
            ['name' => '水平摇镜(右→左)', 'category' => '摇', 'prompt_cn' => '镜头从右向左水平摇动，展现场景全貌', 'prompt_en' => 'pan from right to left, scanning the full scene', 'tags' => json_encode(['摇', '水平', '扫描'])],
            ['name' => '垂直摇镜(上→下)', 'category' => '摇', 'prompt_cn' => '镜头从上向下垂直摇动，逐步展现下方场景', 'prompt_en' => 'tilt down, gradually revealing scene below', 'tags' => json_encode(['摇', '垂直', '俯视'])],
            ['name' => '垂直摇镜(下→上)', 'category' => '摇', 'prompt_cn' => '镜头从下向上垂直摇动，表现仰视或崇高感', 'prompt_en' => 'tilt up, conveying majesty or upward gaze', 'tags' => json_encode(['摇', '垂直', '仰视'])],
            // 移 (Move/Track)
            ['name' => '跟拍(侧跟)', 'category' => '移', 'prompt_cn' => '镜头从侧面跟随主体移动，保持主体在画面中的相对位置不变', 'prompt_en' => 'side tracking shot, following subject while keeping them in frame', 'tags' => json_encode(['移', '跟拍', '侧面'])],
            ['name' => '跟拍(前跟)', 'category' => '移', 'prompt_cn' => '镜头在主体前方后退跟拍，展示主体正面表情和动作', 'prompt_en' => 'front tracking shot, capturing subject\'s facial expressions', 'tags' => json_encode(['移', '跟拍', '正面'])],
            ['name' => '跟拍(后跟)', 'category' => '移', 'prompt_cn' => '镜头在主体后方跟拍，营造神秘感或跟随感', 'prompt_en' => 'rear tracking shot, creating mystery or following sensation', 'tags' => json_encode(['移', '跟拍', '后方'])],
            ['name' => '横移镜头', 'category' => '移', 'prompt_cn' => '镜头沿水平方向横向移动，与画面平行运动', 'prompt_en' => 'dolly shot, moving horizontally parallel to the scene', 'tags' => json_encode(['移', '横移', '平移'])],
            // 跟 (Follow)
            ['name' => '斯坦尼康跟随', 'category' => '跟', 'prompt_cn' => '使用稳定器平滑跟随主体移动，画面流畅稳定', 'prompt_en' => 'steadicam follow, smooth and stable tracking of subject', 'tags' => json_encode(['跟', '稳定', '跟随'])],
            ['name' => '手持跟拍', 'category' => '跟', 'prompt_cn' => '手持摄影机跟拍，画面有轻微晃动，增强真实感和临场感', 'prompt_en' => 'handheld follow cam, slight camera shake for realism', 'tags' => json_encode(['跟', '手持', '真实'])],
            // 升 (Rise/Crane)
            ['name' => '升降镜头(上升)', 'category' => '升', 'prompt_cn' => '镜头从低处向上升起，视野从局部扩展到全景', 'prompt_en' => 'crane up shot, rising to reveal wider view from narrow focus', 'tags' => json_encode(['升', '升降', '扩展'])],
            ['name' => '升降镜头(下降)', 'category' => '降', 'prompt_cn' => '镜头从高处下降，画面从全景聚焦到局部细节', 'prompt_en' => 'crane down shot, descending from wide view to details', 'tags' => json_encode(['降', '升降', '聚焦'])],
            // 旋转 (Rotate)
            ['name' => '环绕拍摄', 'category' => '旋转', 'prompt_cn' => '镜头围绕主体做圆周运动，展现主体全方位视角', 'prompt_en' => 'orbital shot, camera circles around subject showing all angles', 'tags' => json_encode(['旋转', '环绕', '全方位'])],
            ['name' => '旋转镜头', 'category' => '旋转', 'prompt_cn' => '镜头以自身轴线旋转，产生眩晕或迷幻效果', 'prompt_en' => 'dutch angle spin, camera rotates on its axis', 'tags' => json_encode(['旋转', '眩晕', '特效'])],
            // 变焦
            ['name' => '变焦推拉(希区柯克)', 'category' => '特效', 'prompt_cn' => '镜头焦距变化与机身移动相反，主体大小不变而背景变形', 'prompt_en' => 'dolly zoom, vertigo effect, subject size constant background compresses', 'tags' => json_encode(['变焦', '希区柯克', '特效'])],
            ['name' => '快速变焦', 'category' => '特效', 'prompt_cn' => '快速变焦到主体面部特写，强调情绪反应', 'prompt_en' => 'crash zoom to close-up, emphasizing emotional reaction', 'tags' => json_encode(['变焦', '快速', '情绪'])],
            // 景别
            ['name' => '远景(建立镜头)', 'category' => '景别', 'prompt_cn' => '大远景展示整体环境，人物在画面中很小或不可见', 'prompt_en' => 'extreme wide shot, establishing environment, subjects tiny', 'tags' => json_encode(['景别', '远景', '环境'])],
            ['name' => '全景', 'category' => '景别', 'prompt_cn' => '展示人物全身和周围环境的关系', 'prompt_en' => 'full shot, showing full body and environment relationship', 'tags' => json_encode(['景别', '全景', '全身'])],
            ['name' => '中景', 'category' => '景别', 'prompt_cn' => '拍摄人物膝盖以上，适合表现动作和对话', 'prompt_en' => 'medium shot, knees up, good for action and dialogue', 'tags' => json_encode(['景别', '中景', '动作'])],
            ['name' => '近景', 'category' => '景别', 'prompt_cn' => '拍摄人物胸部以上，突出面部表情和情感', 'prompt_en' => 'medium close-up, chest up, emphasizing expressions', 'tags' => json_encode(['景别', '近景', '表情'])],
            ['name' => '特写', 'category' => '景别', 'prompt_cn' => '拍摄人物面部或物体细节，极度聚焦', 'prompt_en' => 'close-up, face or object detail, intense focus', 'tags' => json_encode(['景别', '特写', '细节'])],
            ['name' => '大特写', 'category' => '景别', 'prompt_cn' => '极度特写，仅拍摄眼睛、嘴唇等局部细节', 'prompt_en' => 'extreme close-up, eyes or lips only, maximum detail', 'tags' => json_encode(['景别', '大特写', '极致'])],
            // 打斗场景动作
            ['name' => '拳击特写', 'category' => '打斗', 'prompt_cn' => '拳头击中目标的瞬间特写，冲击力强，碎片/汗珠四溅', 'prompt_en' => 'close-up of fist impact, debris and sweat flying', 'tags' => json_encode(['打斗', '特写', '冲击'])],
            ['name' => '剑气/魔法攻击', 'category' => '打斗', 'prompt_cn' => '角色释放强力攻击，能量波/剑气向前推进，粒子特效环绕', 'prompt_en' => 'character unleashing energy attack, particle effects surrounding', 'tags' => json_encode(['打斗', '魔法', '特效'])],
            ['name' => '闪避动作', 'category' => '打斗', 'prompt_cn' => '角色快速闪避攻击，身体后仰/侧移，残影效果', 'prompt_en' => 'character dodging swiftly, afterimage motion blur', 'tags' => json_encode(['打斗', '闪避', '速度'])],
            // 情感场景
            ['name' => '含泪眼神', 'category' => '情感', 'prompt_cn' => '角色眼中含泪，泪光闪烁，眼神复杂，近景特写', 'prompt_en' => 'character with tears in eyes, glistening, complex emotions', 'tags' => json_encode(['情感', '特写', '眼泪'])],
            ['name' => '微笑转身', 'category' => '情感', 'prompt_cn' => '角色微笑转身，发丝或衣袂飘动，慢动作效果', 'prompt_en' => 'character smiling while turning, hair flowing, slow motion', 'tags' => json_encode(['情感', '转身', '微笑'])],
        ];

        foreach ($actions as $i => $action) {
            DB::table('action_templates')->insertOrIgnore(array_merge($action, [
                'sort_order' => $i + 1,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
