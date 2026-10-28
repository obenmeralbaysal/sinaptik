<?php

namespace App\Consent;

final class GoogleConnectConsent
{
    public const TEXT = 'Google hesabımın bağlanması amacıyla hesap verilerimin ABD\'deki sunuculara aktarılmasına, bu ülkede Türkiye\'deki düzeyde koruma bulunmayabileceğini bilerek açık rıza veriyorum. Bu rızayı bağlantıyı panelden kaldırarak istediğim zaman geri alabilirim.';

    public const LINK_LABEL = 'Aydınlatma Metni';

    public static function version(): string
    {
        return 'sha256:'.hash('sha256', self::TEXT);
    }
}
