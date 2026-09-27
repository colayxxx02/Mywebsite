<?php
function getAvatarUrl($avatar_name, $user_name = 'User') {
    // Kon custom image ang gi-upload sa user
    if (!empty($avatar_name) && strpos($avatar_name, 'avatar_') === 0 && file_exists('uploads/' . $avatar_name)) {
        return 'uploads/' . $avatar_name;
    }

    // High quality cartoon vector avatars (DiceBear API)
    $avatars = [
        // Men Avatars
        'man1'   => 'https://api.dicebear.com/7.x/avataaars/svg?seed=Felix&hair=shortCombover&facialHair=beardLight&facialHairProbability=100&shirtColor=f59e0b',
        'man2'   => 'https://api.dicebear.com/7.x/avataaars/svg?seed=Jack&hair=shortHair&skinColor=edb98a',
        
        // Women Avatars
        'woman1' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=Molly&hair=longHair&hairColor=4a312c&clothesColor=06b6d4',
        'woman2' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=Abby&hair=straightAndStrand&hairColor=2c1b18'
    ];

    return $avatars[$avatar_name] ?? $avatars['man1'];
}
?>