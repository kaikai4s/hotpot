<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

class ProfanityFilterService
{
    /**
     * 不文明词汇列表
     */
    private array $profanityWords = [
        // 常见不文明词汇（示例，实际应使用更完整的词库）
        '傻逼', '傻B', 'SB', 'sb',
        '草', '操', 'cao',
        '妈的', '他妈的', '你妈的',
        '滚', '滚蛋',
        '垃圾', '废物',
        '白痴', '智障',
        '死', '去死',
        '神经病', '有病',
        '脑残', '弱智',
        '贱', '贱人',
        '婊子', '妓女',
        '王八', '王八蛋',
        '狗', '狗东西',
        '猪', '蠢猪',
        '变态', '恶心',
        '操你', '干你',
        '日你', '艹你',
        'fuck', 'shit', 'damn', 'bitch', 'asshole', 'bastard',
        // 可以添加更多敏感词
    ];

    /**
     * 检测文本是否包含不文明语言
     *
     * @param string|null $text 要检测的文本
     * @return array ['has_profanity' => bool, 'matched_words' => array]
     */
    public function checkProfanity(?string $text): array
    {
        if (empty($text)) {
            return [
                'has_profanity' => false,
                'matched_words' => [],
            ];
        }

        $text = mb_strtolower($text, 'UTF-8');
        $matchedWords = [];

        foreach ($this->profanityWords as $word) {
            $wordLower = mb_strtolower($word, 'UTF-8');
            if (mb_strpos($text, $wordLower) !== false) {
                $matchedWords[] = $word;
            }
        }

        return [
            'has_profanity' => !empty($matchedWords),
            'matched_words' => $matchedWords,
        ];
    }

    /**
     * 检测评价内容（包括文本和标签）
     *
     * @param string|null $content 评价内容
     * @param array|null $tags 评价标签
     * @return array ['has_profanity' => bool, 'matched_words' => array]
     */
    public function checkReview(?string $content, ?array $tags = null): array
    {
        $allText = $content ?? '';
        
        // 检查标签
        if (!empty($tags) && is_array($tags)) {
            $allText .= ' ' . implode(' ', $tags);
        }

        return $this->checkProfanity($allText);
    }
}

