<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ProfanityFilterService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfanityFilterServiceTest extends TestCase
{
    private ProfanityFilterService $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = new ProfanityFilterService();
    }

    #[Test]
    // Ref: TSD Section 7.4.1 - 不文明语言过滤
    public function check_profanity_returns_false_when_text_is_clean(): void
    {
        // Arrange
        $text = '这家店的服务很好，菜品也很美味';

        // Act
        $result = $this->sut->checkProfanity($text);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('has_profanity', $result);
        $this->assertArrayHasKey('matched_words', $result);
        $this->assertFalse($result['has_profanity']);
        $this->assertEmpty($result['matched_words']);
    }

    #[Test]
    // Ref: TSD Section 7.4.1 - 不文明语言过滤
    public function check_profanity_returns_true_when_text_contains_profanity(): void
    {
        // Arrange
        $text = '这家店的服务很垃圾';

        // Act
        $result = $this->sut->checkProfanity($text);

        // Assert
        $this->assertIsArray($result);
        $this->assertTrue($result['has_profanity']);
        $this->assertNotEmpty($result['matched_words']);
    }

    #[Test]
    // Ref: TSD Section 7.4.1 - 不文明语言过滤
    public function check_profanity_returns_false_when_text_is_empty(): void
    {
        // Act
        $result = $this->sut->checkProfanity(null);

        // Assert
        $this->assertIsArray($result);
        $this->assertFalse($result['has_profanity']);
        $this->assertEmpty($result['matched_words']);
    }

    #[Test]
    // Ref: TSD Section 7.4.1 - 不文明语言过滤，检测评价内容（包括文本和标签）
    public function check_review_checks_both_content_and_tags(): void
    {
        // Arrange
        $content = '这家店很好';
        $tags = ['推荐', '好吃'];

        // Act
        $result = $this->sut->checkReview($content, $tags);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('has_profanity', $result);
        $this->assertArrayHasKey('matched_words', $result);
    }

    #[Test]
    // Ref: TSD Section 7.4.1 - 不文明语言过滤
    public function check_review_detects_profanity_in_tags(): void
    {
        // Arrange
        $content = '这家店很好';
        $tags = ['垃圾', '推荐'];

        // Act
        $result = $this->sut->checkReview($content, $tags);

        // Assert
        $this->assertIsArray($result);
        $this->assertTrue($result['has_profanity']);
        $this->assertNotEmpty($result['matched_words']);
    }

    #[Test]
    // Ref: TSD Section 7.4.1 - 不文明语言过滤
    public function check_profanity_is_case_insensitive(): void
    {
        // Arrange
        $text = '这家店的服务很SB';

        // Act
        $result = $this->sut->checkProfanity($text);

        // Assert
        $this->assertIsArray($result);
        $this->assertTrue($result['has_profanity']);
    }
}

