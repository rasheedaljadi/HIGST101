<?php

namespace App\Services\AliExpress\Learning;

class AliExpressTextTokenizer
{
    /**
     * Common meaningless e-commerce noise words to exclude from keyword learning.
     *
     * @var array<string, bool>
     */
    protected static array $stopwords = [
        'a' => true, 'about' => true, 'above' => true, 'after' => true, 'again' => true,
        'against' => true, 'all' => true, 'am' => true, 'an' => true, 'and' => true,
        'any' => true, 'are' => true, 'as' => true, 'at' => true, 'be' => true,
        'because' => true, 'been' => true, 'before' => true, 'being' => true, 'below' => true,
        'between' => true, 'both' => true, 'but' => true, 'by' => true, 'can' => true,
        'did' => true, 'do' => true, 'does' => true, 'doing' => true, 'down' => true,
        'during' => true, 'each' => true, 'few' => true, 'for' => true, 'from' => true,
        'further' => true, 'had' => true, 'has' => true, 'have' => true, 'having' => true,
        'he' => true, 'her' => true, 'here' => true, 'hers' => true, 'herself' => true,
        'him' => true, 'himself' => true, 'his' => true, 'how' => true, 'i' => true,
        'if' => true, 'in' => true, 'into' => true, 'is' => true, 'it' => true,
        'its' => true, 'itself' => true, 'just' => true, 'me' => true, 'more' => true,
        'most' => true, 'my' => true, 'myself' => true, 'no' => true, 'nor' => true,
        'not' => true, 'now' => true, 'of' => true, 'off' => true, 'on' => true,
        'once' => true, 'only' => true, 'or' => true, 'other' => true, 'ought' => true,
        'our' => true, 'ours' => true, 'ourselves' => true, 'out' => true, 'over' => true,
        'own' => true, 'same' => true, 'she' => true, 'should' => true, 'so' => true,
        'some' => true, 'such' => true, 'than' => true, 'that' => true, 'the' => true,
        'their' => true, 'theirs' => true, 'them' => true, 'themselves' => true, 'then' => true,
        'there' => true, 'these' => true, 'they' => true, 'this' => true, 'those' => true,
        'through' => true, 'to' => true, 'too' => true, 'under' => true, 'until' => true,
        'up' => true, 'very' => true, 'was' => true, 'we' => true, 'were' => true,
        'what' => true, 'when' => true, 'where' => true, 'which' => true, 'while' => true,
        'who' => true, 'whom' => true, 'why' => true, 'with' => true, 'would' => true,
        'you' => true, 'your' => true, 'yours' => true, 'yourself' => true, 'yourselves' => true,
        // E-commerce noise
        'new' => true, 'hot' => true, 'sale' => true, 'best' => true, 'top' => true,
        'original' => true, 'free' => true, 'shipping' => true, 'pcs' => true, 'piece' => true,
        'pieces' => true, 'set' => true, 'brand' => true, 'high' => true, 'quality' => true,
        'wholesale' => true, 'dropshipping' => true, 'drop' => true, 'ship' => true,
        'pro' => true, 'max' => true, 'mini' => true, 'plus' => true, 'lite' => true,
        'men' => true, 'women' => true, 'unisex' => true, 'man' => true, 'woman' => true,
        'boy' => true, 'girl' => true, 'kids' => true, 'child' => true, 'baby' => true,
        'fashion' => true, 'style' => true, 'design' => true, 'casual' => true, 'luxury' => true,
        'simple' => true, 'classic' => true, 'cool' => true, 'cute' => true, 'cheap' => true,
        'good' => true, 'great' => true, 'super' => true, 'ultra' => true, 'fast' => true,
        'easy' => true, 'portable' => true, 'durable' => true, 'upgraded' => true, 'version' => true,
        '2020' => true, '2021' => true, '2022' => true, '2023' => true, '2024' => true,
        '2025' => true, '2026' => true, '2027' => true, '2028' => true,
        'cm' => true, 'mm' => true, 'inch' => true, 'inches' => true, 'kg' => true,
        'g' => true, 'pack' => true, 'lot' => true, 'box' => true, 'bag' => true,
    ];

    /**
     * Tokenize text into meaningful 1-grams, 2-grams, and 3-grams.
     *
     * @return array<int, string>
     */
    public function extractTokens(string $text): array
    {
        // 1. Strip HTML tags
        $clean = strip_tags($text);

        // 2. Decode entities
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 3. Lowercase
        $clean = mb_strtolower($clean, 'UTF-8');

        // 4. Replace non-alphanumeric (keep Arabic + Latin + numbers) with space
        $clean = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $clean) ?? '';

        // 5. Split words
        $words = preg_split('/\s+/u', trim($clean), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // 6. Filter single words (remove purely numeric, single characters, or stopwords)
        $filtered = [];
        foreach ($words as $word) {
            $len = mb_strlen($word, 'UTF-8');
            if ($len < 2 || is_numeric($word)) {
                continue;
            }
            if (isset(self::$stopwords[$word])) {
                continue;
            }
            $filtered[] = $word;
        }

        $tokens = [];

        // 7. Add 1-grams
        foreach ($filtered as $w) {
            $tokens[] = $w;
        }

        // 8. Add 2-grams
        $count = count($filtered);
        for ($i = 0; $i < $count - 1; $i++) {
            $tokens[] = $filtered[$i].' '.$filtered[$i + 1];
        }

        // 9. Add 3-grams
        for ($i = 0; $i < $count - 2; $i++) {
            $tokens[] = $filtered[$i].' '.$filtered[$i + 1].' '.$filtered[$i + 2];
        }

        return array_values(array_unique($tokens));
    }
}
