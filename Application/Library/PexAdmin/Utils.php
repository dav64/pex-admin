<?php
class PexAdmin_Utils
{
    /**
     * Check if application is configured properly and all data is available
     * @param string $message
     * @return boolean
     *      true: if all is ok
     *      false: an error occured, more info is put in $message var
     */
    public static function checkState(&$message = null)
    {
        $result = true;
        $message = 'No error';

        try
        {
            // Run fake requests
            Model_Permission::getPermissions('test', Model_Permission::TYPE_GROUP);
            Model_PermissionsEntity::getEntitiesByType(Model_Permission::TYPE_GROUP);
            Model_PermissionsInheritance::getGroupsParents();
        }
        catch (Exception $e)
        {
            $result = false;
            $message = $e->getMessage();
        }

        return $result;
    }

    /**
     * Get Mojang player UUID from name
     * @param string $playername
     * @param bool $withHyphens
     * @return string
     *      The official UUID of the specified player
     */
    public static function getMojangUuid($playername, $withHyphens = true)
    {
        $error = false;
        $result = false;

        $session = Oxygen_Session::getInstance();
        $cachedUuids = $session->uuids;

        if (!isset($cachedUuids[$playername]))
        {
            try {
                $content = file_get_contents('https://api.mojang.com/users/profiles/minecraft/'.$playername);

                $response = @json_decode($content, true);

                if (!empty($content) && json_last_error() == 0)
                    $cachedUuids[$playername] = $response['id'];
                else if (empty($content))
                    $cachedUuids[$playername] = false;
                else
                {
                    $result = 'Mojang error (malformed response)';
                    $error = true;
                }

                if (isset($cachedUuids[$playername]))
                    $result = $cachedUuids[$playername];

                $session->uuids = $cachedUuids;
            } catch (Exception $e) {}
        }
        else
            $result = $cachedUuids[$playername];

        if (!$result)
            $result = 'Player not found!';
        else if (!$error && $withHyphens)
            $result = self::addHyphens($result);

        return $result;
    }

    /**
     * Generate player UUID from name (to use in offline servers)
     * @param string $playername
     * @param bool $withHyphens
     * @return string
     *      The offline UUID of the specified player
     */
    public static function generateOfflineUuid($playername, $withHyphens = true)
    {
        $result = md5("OfflinePlayer:".$playername);

        $result[12] = '3';

        if ($withHyphens)
            $result = self::addHyphens($result);

        return $result;
    }

    /**
     * Hyphenize a provided UUID
     * @param string $uuid
     * @return string
     *      The hyphened UUID
     */
    public static function addHyphens($uuid)
    {
        $result = $uuid;

        $result = substr($result, 0, 8 ) .'-'.
          substr($result, 8, 4) .'-'.
          substr($result, 12, 4) .'-'.
          substr($result, 16, 4) .'-'.
          substr($result, 20)
        ;

        return $result;
    }

    // Adapted from http://www.minecraftforum.net/forums/mapping-and-modding-java-edition/minecraft-tools/1264944
    public static function convertMinetextToWeb($minetext)
    {
        $colors = [
            '0' => 'black',
            '1' => 'dark-blue',
            '2' => 'dark-green',
            '3' => 'dark-aqua',
            '4' => 'dark-red',
            '5' => 'dark-purple',
            '6' => 'gold',
            '7' => 'gray',
            '8' => 'dark-gray',
            '9' => 'blue',
            'a' => 'green',
            'b' => 'aqua',
            'c' => 'red',
            'd' => 'light-purple',
            'e' => 'yellow',
            'f' => 'white',
        ];

        $formats = [
            'k' => 'obfuscated',
            'l' => 'bold',
            'm' => 'strike',
            'n' => 'underline',
            'o' => 'italic',
        ];

        preg_match_all ("/[^§&]*[^§&]|[§&][0-9a-z][^§&]*/", $minetext, $brokenupstrings);
        $returnstring = "";
        foreach ($brokenupstrings as $results)
        {
            $ending = '';
            foreach ($results as $individual)
            {
                $code = preg_split("/[&§][0-9a-z]/", $individual);
                preg_match("/[&§][0-9a-z]/", $individual, $prefix);
                if (isset ($prefix[0]))
                {
                    $actualcode = substr($prefix[0], 1);

                    if (isset($colors[$actualcode]))
                    {
                        $returnstring .= '<span class="mc-'.$colors[$actualcode].'">';
                        $ending .= '</span>';
                    }
                    else if (isset($formats[$actualcode]))
                    {
                        $returnstring .= '<span class="'.$formats[$actualcode].'">';
                        $ending = '</span>' . $ending;
                    }
                    if (isset ($code [1])) {
                        $returnstring .= $code [1];
                        if (isset ($ending) && strlen ($individual) > 2) {
                            $returnstring .= $ending;
                            $ending = '';
                        }
                    }
                } else {
                    $returnstring .= $individual;
                }
            }
        }

        return $returnstring;
    }
}
