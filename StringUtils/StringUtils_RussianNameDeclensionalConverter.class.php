<?php
/**
 * Склонение русских имён и фамилий
 *
 * Список констант по падежам см. ниже в коде.
 *
 * Пожалуйста, присылайте свои уточнения мне на почту. Спасибо.
 *
 * @version  0.1.3
 * @author   Johnny Woo <agalkin@agalkin.ru>
 * 
 * 
 * 
 * Подправил под php5 и стандарты Webproduction Михаил Богданов 
 * Пример использования
 * $name => StringUtils_RussianNameDeclensionalConverter::ConvertFullNameToPrepositional(array('mname'=> 'Петрович','lname'=>'Сидоров','fname' => 'Иван'));
 * получим Петровиче Сидорове Иване
 * (по соответствующей комбинации ключей получаем нужную последовательность имени, отчества и фамилии)
 * 
 * 
 */

//mb_internal_encoding("UTF-8");

class StringUtils_RussianNameDeclensionalConverter {
    
    
/**
* ПОЛЬЗОВАТЕЛЬСКИЕ МЕТОДЫ
*/
 
 
 
     /**
     * Преобразуем полное имя в родительный падеж
     * @param ассоциативный массив $name - ОБЯЗАТЕЛЬНО использовать ключи ('fname','mname','lname'), где
     * ключу fname будет соответствовать имя, ключу 'mname' - отчество, ключу 'lname' - фамилия, при этом
     * порядок ключей выбираем такой - киким хотим видеть последовательность имени, фамилии и отчество,
     * ВОЗМОЖНО отсутсвие любого ключа (конечно с соответствующим значением)     * 
     * @return string $name, родительный падеж
     */
    public static function ConvertFullNameToGenitive($name) {
        $russian_name_object = new StringUtils_RussianNameDeclensionalConverter($name);
        return $russian_name_object->_fullName($name,$russian_name_object->gcaseRod);
    }
    
    /**
     * Преобразуем полное имя в дательный падеж
     * @param ассоциативный массив $name - ОБЯЗАТЕЛЬНО использовать ключи ('fname','mname','lname'), где
     * ключу fname будет соответствовать имя, ключу 'mname' - отчество, ключу 'lname' - фамилия, при этом
     * порядок ключей выбираем такой - киким хотим видеть последовательность имени, фамилии и отчество,
     * ВОЗМОЖНО отсутсвие любого ключа (конечно с соответствующим значением)
     * @return string $name, дательный падеж
     */
    public static function ConvertFullNameToDative($name) {
        $russian_name_object = new StringUtils_RussianNameDeclensionalConverter($name);
        return $russian_name_object->_fullName($name,$russian_name_object->gcaseDat);
    }
    
    /**
     * Преобразуем полное имя в винительный падеж
     * @param ассоциативный массив $name - ОБЯЗАТЕЛЬНО использовать ключи ('fname','mname','lname'), где
     * ключу fname будет соответствовать имя, ключу 'mname' - отчество, ключу 'lname' - фамилия, при этом
     * порядок ключей выбираем такой - киким хотим видеть последовательность имени, фамилии и отчество,
     * ВОЗМОЖНО отсутсвие любого ключа (конечно с соответствующим значением)
     * @return string $name, винительный падеж
     */
    public static function ConvertFullNameToAccusative($name) {
        $russian_name_object = new StringUtils_RussianNameDeclensionalConverter($name);
        return $russian_name_object->_fullName($name,$russian_name_object->gcaseVin);
    }
    
     /**
     * Преобразуем полное имя в Творительный падеж
     * @param ассоциативный массив $name - ОБЯЗАТЕЛЬНО использовать ключи ('fname','mname','lname'), где
     * ключу fname будет соответствовать имя, ключу 'mname' - отчество, ключу 'lname' - фамилия, при этом
     * порядок ключей выбираем такой - киким хотим видеть последовательность имени, фамилии и отчество,
     * ВОЗМОЖНО отсутсвие любого ключа (конечно с соответствующим значением)
     * @return string $name, Творительный падеж
     */
    public static function ConvertFullNameToInstrumentative($name) {
        $russian_name_object = new StringUtils_RussianNameDeclensionalConverter($name);
        return $russian_name_object->_fullName($name,$russian_name_object->gcaseTvor);
    }
    
    
    /**
     * Преобразуем Имя в предложный падеж
     * @param string $name - имя (Именительный падеж)
     * @return string $name, предложный падеж
     */
    public static function ConvertFullNameToPrepositional($name) {
        $russian_name_object = new StringUtils_RussianNameDeclensionalConverter($name);
        return $russian_name_object->_fullName($name,$russian_name_object->gcasePred);
    }
  
    
    
    
    
    
/**
* СИСТЕМНЫЕ СВОЙСТВА И МЕТОДЫ
*/
    

    private $_lastName = array(
        'exceptions' => array(
        " дюма,тома,дега,люка,ферма,гамарра,петипа . . . . .",
        ' гусь,ремень,камень,онук,богода,нечипас,долгопалец,маненок,рева,кива . . . . .',
        ' вий,сой,цой,хой --я -ю -я -ем --е'
        ),
        'suffixes' => array(
        'f б,в,г,д,ж,з,й,к,л,м,н,п,р,с,т,ф,х,ц,ч,ш,щ,ъ,ь . . . . .',
        'f ска,цка  --ой -ой -ую -ой --ой',
        'f ая       ----ой --ой --ую --ой ----ой',
        ' ская     ----ой --ой --ую --ой ----ой',
        'f на       --ой -ой -у -ой --ой',
    
        ' иной --я -ю -я -ем --е',
        ' уй   --я -ю -я -ем --е',
        ' ца   --ы -е -у -ей --е',
    
        ' рих  а у а ом е',
    
        ' ия                      . . . . .',
        ' иа,аа,оа,уа,ыа,еа,юа,эа . . . . .',
        ' их,ых                   . . . . .',
        ' о,е,э,и,ы,у,ю           . . . . .',
    
        ' ова,ева            --ой -ой -у -ой --ой',
        ' га,ка,ха,ча,ща,жа  --и -е -у -ой --е',
        ' ца  --и -е -у -ей --е',
        ' а   --ы -е -у -ой --е',
    
        ' ь   --я -ю -я -ем --е',
    
        ' ия  --и -и -ю -ей --и',
        ' я   --и -е -ю -ей --е',
        ' ей  --я -ю -я -ем --е',
    
        ' ян,ан,йн   а у а ом е',
    
        ' ынец,обец  ----ца --цу --ца --цем ----це',
        ' онец,овец  ----ца --цу --ца --цом ----це',
    
        ' ц,ч,ш,щ   а у а ем е',
    
        ' ай  --я -ю -я -ем --е',
        ' ой  --го -му -го --им --м',
        ' ах,ив   а у а ом е',
    
        ' ший,щий,жий  ----его --ему --его -м ----ем',//отсюда ний убрал
        ' ний       --я -ю -я -ем ----ие',//моя добавки на укр фамилию на ний (Стогний)
        ' кий,ый   ----ого --ому --ого -м ----ом',
        ' ий       --я -ю -я -ем ----ии',
        
    
        ' ок  ----ка --ку --ка --ком ----ке',
        ' ец  ----ца --цу --ца --цом ----це',
    
        ' в,н   а у а ым е',
        ' б,г,д,ж,з,к,л,м,п,р,с,т,ф,х   а у а ом е'
        )
        );
    
    private $_firstName = array(
        'exceptions' => array (
        ' лев    ----ьва --ьву --ьва --ьвом ----ьве',
        ' павел  ----ла  --лу  --ла  --лом  ----ле',
        'm шота   . . . . .',
        'f рашель,нинель,николь,габриэль,даниэль   . . . . .'
        ),
        'suffixes' => array(
        ' е,ё,и,о,у,ы,э,ю   . . . . .',
        'f б,в,г,д,ж,з,й,к,л,м,н,п,р,с,т,ф,х,ц,ч,ш,щ,ъ   . . . . .',
    
        'f ь   --и -и . ю --и',
        'm ь   --я -ю -я -ем --е',
    
        ' га,ка,ха,ча,ща,жа  -и -е -у -ой --е',
        ' а   --ы -е -у -ой --е',
        ' ия  --и -и -ю -ей ----ии',
        ' я   --и -е -ю -ей --е',
        ' ей  --я -ю -я -ем ----ее',
        ' ий  --я -ю -я -ем ----ии',
        ' й   --я -ю -я -ем --е',
        ' б,в,г,д,ж,з,к,л,м,н,п,р,с,т,ф,х,ц,ч  а у а ом е'
            )
    );

    private $_middleName = array(
        'exceptions' => array (),
        'suffixes' => array (
        ' ич   а  у  а  ем  е',
        ' на  --ы -е -у -ой ----не'
        )
    );


    private $_sexM = 'm';
    private $_sexF = 'f';
    public $gcaseIm =  'nominative';      public $gcaseNom = 'nominative';      // именительный
    public $gcaseRod = 'genitive';        public $gcaseGen = 'genitive';        // родительный
    public $gcaseDat = 'dative';                                       // дательный
    public $gcaseVin = 'accusative';      public $gcaseAcc = 'accusative';      // винительный
    public $gcaseTvor = 'instrumentative';public $gcaseIns = 'instrumentative'; // творительный
    public $gcasePred = 'prepositional';  public $gcasePos = 'prepositional';   // предложный

    private $_fullNameSurnameLast = false;
    private $_ln = '', $_fn = '', $_mn = '', $_sex = '';

    private $_rules;
    private $_initialized = false;

    private function _init(){
        if ( $this->_initialized ) {
            return;
        }

        $this->_rules = array('lastName'=>$this->_lastName, 'firstName'=>$this->_firstName, 'middleName' => $this->_middleName);
        $this->_prepareRules();
        $this->_initialized = true;
    }

   
    private function __construct (array $name, $sex = NULL) {
      
        $this->_init();
        
        isset($name['fname']) ? $this->_fn = $name['fname'] : $this->_fn = '';
        isset($name['mname']) ? $this->_mn = $name['mname'] : $this->_mn = '';
        isset($name['lname']) ? $this->_ln = $name['lname'] : $this->_ln = '';
        
        if (isset($sex)) $this -> _sex = $sex;
        else $this -> _sex = $this -> _getSex();
        return;
    }

    private function _prepareRules () {
        foreach ( array("lastName", "firstName", "middleName") as $type ) {
            foreach(array("suffixes" ,"exceptions") as $key) {
                $n = count($this -> _rules[$type][$key]);
                for ($i = 0; $i < $n; $i++) {
                    $this->_rules[$type][$key][$i] = $this->_rule($this->_rules[$type][$key][$i]);
                }
            }
        }
    }





    private function _rule ($rule) {
        preg_match("/^\s*([fm]?)\s*(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s*$/", $rule, $m);
        if ( $m ) return array (
        "sex" => $m[1],
        "test" => explode(',', $m[2]),
        "mods" => array ($m[3], $m[4], $m[5], $m[6], $m[7])
        );
        return false;
    }

    // склоняем слово по указанному набору правил и исключений
    private function _word ($word, $sex, $wordType, $gcase) {
        // исходное слово находится в именительном падеже
        if( $gcase == $this->gcaseNom) return $word;

        // составные слова
        if( preg_match("/[-]/", $word)) {
            $list = explode('-',$word);
            $n = count($list);
            for($i = 0; $i < $n; $i++) {
                $list[$i] = $this->_word($list[$i], $sex, $wordType, $gcase);
            }
            return join('-', $list);
        }

        // Иванов И. И.
        if ( preg_match("/^[А-ЯЁ]\.?$/i", $word)) return $word;
        $this->_init();
        $rules = $this->_rules[$wordType];

        if ( $rules['exceptions']) {
            $pick = $this->_pick($word, $sex, $gcase, $rules['exceptions'], true); 
            if ( $pick ) return $pick;
        }
        $pick = $this->_pick($word, $sex, $gcase, $rules['suffixes'], false);
        if ($pick) return $pick;
        else return $word;
    }

    // выбираем из списка правил первое подходящее и применяем
    private function _pick ($word, $sex, $gcase, $rules, $matchWholeWord) {
        $wordLower =  mb_strtolower($word);
        $n = count($rules);
        for($i = 0; $i < $n; $i++) {
            if ( $this->_ruleMatch($wordLower, $sex, $rules[$i], $matchWholeWord)) {
                return $this->_applyMod($word, $gcase, $rules[$i]);
            }
        }
        return false;
    }


    // проверяем, подходит ли правило к слову
    private function _ruleMatch ($word, $sex, $rule, $matchWholeWord) {
        if ($rule["sex"] == $this->_sexM && $sex == $this->_sexF) return false; // male by default
        if ($rule["sex"] == $this->_sexF && $sex != $this->_sexF) return false;
        $n = count($rule["test"]);
        for($i = 0; $i < $n; $i++) {
            $test = $matchWholeWord ? $word : substr($word, max(strlen($word) - strlen($rule["test"][$i]), 0));
            if($test == $rule["test"][$i]) return true;
        }
        return false;
    }

    // склоняем слово (правим окончание)
    private function _applyMod($word, $gcase, $rule) {
        switch($gcase) {
            case $this -> gcaseNom: $mod = '.'; break;
            case $this -> gcaseGen: $mod = $rule["mods"][0]; break;
            case $this -> gcaseDat: $mod = $rule["mods"][1]; break;
            case $this -> gcaseAcc: $mod = $rule["mods"][2]; break;
            case $this -> gcaseIns: $mod = $rule["mods"][3]; break;
            case $this -> gcasePos: $mod = $rule["mods"][4]; break;
            default: exit("Unknown grammatic case: "+gcase);
        }
        $n = strlen($mod);
        for($i = 0; $i < $n; $i++) {
            $c = substr($mod, $i, 1);
            switch($c) {
                case '.': break;
                case '-': $word = substr($word, 0, strlen($word) - 1); break;
                default: $word .= $c;
            }
        }
        return $word;
    }

    private function _getSex() {
        if( strlen($this->_mn) > 2) {

            switch(substr($this->_mn, -2)) {
                case 'ич': return $this->_sexM;
                case 'на': return $this->_sexF;
            }
        }
        return '';
    }
    
    
    private function _fullName($name,$gcase) {
        $str = '';
        foreach($name as $k => $val){
            if($k == 'fname'){
                $str .= $this -> _firstName($gcase).' ';
            }
            elseif($k == 'mname'){
                $str .= $this ->_middleName($gcase).' ';
            }
            elseif($k == 'lname'){
                $str .= $this ->_lastName($gcase).' ';
            }
        }
        
        return trim($str);
    }

    private function _lastName($gcase) {
        return $this->_word($this -> _ln, $this -> _sex, 'lastName', $gcase);
    }

    private function _firstName($gcase) {
        return $this->_word($this -> _fn, $this -> _sex, 'firstName', $gcase);
    }

    private function _middleName($gcase) {
        return $this->_word($this -> _mn, $this -> _sex, 'middleName', $gcase);
    }
    
}