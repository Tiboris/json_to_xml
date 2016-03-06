#!/usr/bin/php
<?php
    function help()
    {
        $help=
        "\t--input=filename\n 
            \t\t(UTF-8) in json\n\n
        \t--outpu=filename 
            \t\t(UTF-8) in XML\n\n
        \t-h=subst    
            \t\tve jméně elementu odvozeném z dvojice jméno-hodnota nahraďte každý nepovolený\n
            \t\tznak ve jméně XML značky řetězcem subst. Implicitně (i při nezadaném parametru -h) uvažu-\n
            \t\tjte nahrazování znakem pomlčka (-). Vznikne-li po nahrazení invalidní jméno XML elementu,\n
            \t\tskončete s chybou a návratovým kódem 51\n\n
        \t-n
            \t\tnegenerovat XML hlavičku 1 na výstup skriptu (vhodné například v případě kombinování více výsledků)\n\n
        \t-r=root-element\n
            \t\tjméno párového kořenového elementu obalujícího výsledek. Pokud nebude\n
            \t\tzadán, tak se výsledek neobaluje kořenovým elementem, ač to potenciálně porušuje validitu\n
            \t\tXML (skript neskončí s chybou). Zadání řetězce root-element vedoucího na nevalidní XML\n
            \t\tznačku ukončí skript s chybou a návratovým kódem 50 (nevalidní znaky nenahrazujte).\n\n
        \t--array-name=array-element \n 
            \t\ttento parametr umožní přejmenovat element obalující pole\n
            \t\tz implicitní hodnoty array na array-element. Zadání řetězce array-element vedoucího na\n
            \t\tnevalidní XML značku ukončí skript s chybou a návratovým kódem 50 (nevalidní znaky ne-\n
            \t\tnahrazujte).\n\n
        \t--item-name=item-element    \n
            \t\tanalogicky, tímto parametrem lze změnit jméno elementu pro\n
            \t\tprvky pole (implicitní hodnota je item). Zadání řetězce item-element vedoucího na nevalidní\n
            \t\tXML značku ukončí skript s chybou a návratovým kódem 50 (nevalidní znaky nenahrazujte).\n\n
        \t-s  \n
            \t\thodnoty (v dvojici i v poli) typu string budou transformovány na textové elementy místo atributů.\n\n
        \t-i  \n
            \t\thodnoty (v dvojici i v poli) typu number budou transformovány na textové elementy místo atributů.\n\n
        \t-l  \n
            \t\thodnoty literálů (true, false, null) budou transformovány na elementy <true/>,<false/> a <null/> místo na atributy\n\n
        \t-c  \n
            \t\taktivuje překlad problematických znaků.\n\n
        \t-a, --array-size  \n  
            \t\tu pole bude doplněn atribut size s uvedením počtu prvků v tomto poli\n\n
        \t--start=n   \n
            \t\tinicializace inkrementálního čitače pro indexaci prvků pole na zadané kladné celé\n 
            \t\tčíslo n včetně nuly (implicitně n = 1)\n
            \t\t(nutno kombinovat s parametrem --index-items, jinak chyba s návratovým kódem 1)\n\n
        \t-t, --index-items \n  
            \t\tke každému prvku pole bude přidán atribut index s určením indexu prvku\n
            \t\tv tomto poli (číslování začíná od 1, pokud není parametrem --start určeno jinak).\n";
        
        echo $help;
        exit(0);
    }

    function err($errcode) 
    {
        echo "Program error, exit code '" . $errcode . "' type '--help' for more info.\n" ; // stderr
        die($errcode);
    }

    function parse_args($args, $count)
    {
        $shrt_opt_rex       = "^-(s|n|i|l|c|a|t)$";
        $long_opt_rex       = "^--(index-items|array-size)$";
        $shrt_opt_rex_val   = "^-(r|h)=(.*)";
        $long_opt_rex_val   = "^--(input|output|array-name|item-name|start)=(.*)";

        if ($count == 1) { 
            return false;
        }
        if ( ( $count == 2 ) && ( $args[1] === "--help" ) ) {
            help();
        }
        for ( $i = 1; $i <= $count-1; $i++ ) 
        { 
            if( ereg($long_opt_rex_val, $args[$i], $option) || ereg($shrt_opt_rex_val, $args[$i], $option) )
            {
                if ( ! isset($parsing[$option[1]]) && ( $option[2] != null ) ) { 
                    $parsing[$option[1]] = $option[2];
                }
                else {               
                    return false;
                }
            }
            elseif ( ereg($shrt_opt_rex, $args[$i], $option) || ereg($long_opt_rex, $args[$i], $option) )
            {
                if ( ! isset($parsing[$option[1]]) ) {
                    $parsing[$option[1]] = true;
                }
                else {
                    return false;
                }
            }
            else {
                return false;       
            }
        }
        return $parsing;
    }

    function check_args($args)
    {
        if ( $args === false ) { 
            return false;
        }
        if ( isset( $args['start'] ) ) 
        {
            if ( ! isset( $args['index-items'] ) || ( ereg("^[0-9]*$", $args['start']) ) === false  ) {
                return false;
            }
            else {
                $args['start']=(int)$args['start']; 
            }
        }
        else {
            $args['start']=1;
        }
        if ( ! isset( $args['h'] ) ) {
            $args['h'] = "-"; 
        }
        if ( ! isset( $args['array-name'] ) ) {
            $args['array-name']="array";
        }
        if ( ! isset( $args['item-name'] ) ) {
            $args['item-name']="item";
        }
        if ( isset($args['array-size']) && $args['a'] ) {
            return false;
        } 
        return $args;
    }
    
    function write($json_input, $args)
    {
        $writer = new XMLWriter();
        $writer->openURI(realpath($args['output']));
        if ( ! isset($args['n']) ) {
            $writer->startDocument('1.0','UTF-8');
        }
        $writer->setIndent(true);
        //var_dump($args);
        // or
        //print_r($json_input);
        write_xml( $writer, $json_input, $args );
    }
    
    function write_xml($writer, $json_input, $args)
    {
        foreach ($json_input as $key => $value) 
        {
            $writer->startElement($key); 
            if ( is_object( $value) ) {
                write_xml($writer, $value, $args);
            }
            else if ( is_array( $value) ) { 
                write_array($writer, $value, $args); 
            }
            else {
                $writer->text($value);
            }
            $writer->endElement();
        }
    }

    function write_array($writer, $array, $args)
    {
        $writer->startElement($args['array-name']);
        if ( isset($args['array-size']) || isset($args['a']) ) {
            $writer->writeAttribute('size', count($array));    
        }
        $index = $args['start'];
        foreach ($array as $key => $value)
        {
            $writer->startElement($args['item-name']);
            if ( isset( $args['index-items'] ) ) {
                $writer->writeAttribute('index', $index++);  
            }
            if ( is_object( $value) ) {
                write_xml($writer, $value, $args);
            }
            else if ( is_array( $value) ) { 
                write_array($writer, $value, $args);
            }
            else {
                $writer->text($value);
            }
            $writer->endElement();
        }
        $writer->endElement(); 
    }
    /*
    ** end of function declaration 
    **/

    /*
    ** Input / Output chceking
    **/
    if ( ( $args = check_args( parse_args($argv, $argc) ) ) === false ) {
        err(1);
    }
    if ( ( $json_input_path = realpath($args['input']) ) == NULL ) {
        err(2);
    }
    if ( ( $json_input = file_get_contents($json_input_path) ) === false ) {
        err(2);
    }
    if ( ( $xml_output = fopen($args['output'], 'w') ) === false ) {
        err(3);
    }
    else {
        fclose($xml_output);
    }
    if( ! is_array($json_input = json_decode($json_input, false)) && ! is_object($json_input) ) {
        err(4);
    }
    // starting writer
    write($json_input, $args);
    // end of script    
?>
