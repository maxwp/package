<?php
final class StringUtils_FormatterTimestamp {

    public static function FormatTimestamp($value) {
        //return ((int) ($value * 1_000_000)); // 10**6, // 24 ns, из которых 18 ns это просто пустой call()

        // best of:
        return substr_replace((string)((int) ($value * 1_000_000)), '.', -6, 0); // 82 ns, из которых 18 пустой call
    }

}