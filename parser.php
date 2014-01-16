<?php
header('Content-Type: text/html; charset=utf8');
set_time_limit(100);
// sākuma mainīgie
$input = $_GET["input"]; // ievaddati
$buffer = explode(" ", $input);// ievaddati sadalīti pa vārdam
$ixixix = 1;
foreach ($buffer as &$value) {// sanumurē visus vārdus
	$value=$ixixix."_".$value;
	$ixixix++;
}
$stack = new SplDoublyLinkedList();
$arcs = array();
$moves = "";
$finalResult = array();
// vārdi
$words = array(
	["waiter","noun"],
	["meal","noun"],
	["day","noun"],
	["table","noun"],
	["the","determiner"],
	["a","determiner"],
	["brought","verb"],
	["ran","verb"],
	["of","prep"],
	["zēns","noun"],
	["gāja","verb"],
	["pārliecinošu","verb"],
	["noslēdzās","verb"],
	["uz","prep"],
	["ar","prep"],
	["skolu","noun"],
	["spēle","noun"],
	["uzvaru","noun"],
	["to","prep"]
);
// likumi
$rules = array(
	["noun","determiner","determinative","left"],
	["noun","adjective","attribute","left"],
	["verb","noun","subject","left"],
	["noun","verb","subject","left"],
	["verb","pronoun","subject","left"],
	["verb","noun","object","right"],
	["verb","pronoun","object","right"],
	["verb","prep","adv","right"],
	["verb","prep","adv","left"],
	["noun","prep","pmod","right"],
	["prep","noun","pcomp","right"],
	["stop","verb","sentence","right"],
	["start","stop","sentence2","right"]
);

echo "<pre>";
nivre($stack, $buffer, $arcs, $moves);
echo "</pre>";

function nivre($stack, $buffer, $arcs, $moves){
global $words, $rules;
	if(count($buffer)==0&&$stack->isEmpty()){
		printVars("END", $stack, $buffer, $arcs, $moves);
		return;
	}
	if(count($buffer)!=0){ // vai drīkst SHIFT?
		$stackCopy = copyStack($stack);
		$bufferCopy = $buffer;
		$movesCopy = $moves;
		
		$stackCopy->push(array_shift($bufferCopy));
		$movesCopy.=", SHIFT(".$stackCopy->top().")";
		printVars("SHIFT", $stackCopy, $bufferCopy, $arcs, $movesCopy);
		nivre($stackCopy, $bufferCopy, $arcs, $movesCopy);
	}
	$canBeReduced = 0;
	if(!$stack->isEmpty()){
		foreach($arcs as $arc){
			if(strcmp($arc[1], $stack->top())==0)$canBeReduced = 1;
		}
	}
	if($canBeReduced == 1){ // vai drīkst REDUCE?
		$stackCopy = copyStack($stack);
		$movesCopy = $moves;
		
		$movesCopy.=", REDUCE(".$stackCopy->top().")";
		$stackCopy->pop();
		printVars("REDUCE",$stackCopy, $buffer, $arcs, $movesCopy);
		nivre($stackCopy, $buffer, $arcs, $movesCopy);
	}
	
	$canmakeLeftArc = 1;
	if(count($buffer)!=0&&!$stack->isEmpty()){
		foreach($arcs as $arc){
			if(strcmp($arc[1], $stack->top())==0) $canmakeLeftArc = 0;
		}
	}else{
		$canmakeLeftArc = 0;
	}
	if($canmakeLeftArc == 1){ // vai drīkst LEFTARC?
		$stackCopy = copyStack($stack);		
		$bufferCopy = $buffer;
		$arcsCopy = $arcs;
		$movesCopy = $moves;
		
		if(filterArc(array($bufferCopy[0],$stackCopy->top(),"<-L-"), $words, $rules)){
			$movesCopy.=", LEFT ARC(".$bufferCopy[0].", ".$stackCopy->top().")";
			$arcsCopy[] = array($bufferCopy[0],$stackCopy->pop(),"<-L-");
			printVars("LEFT ARC", $stackCopy, $bufferCopy, $arcsCopy, $movesCopy);
			nivre($stackCopy, $bufferCopy, $arcsCopy, $movesCopy);
		}
	}
		
	$canmakeRightArc = 1;
	if(count($buffer)!=0&&!$stack->isEmpty()){
		foreach($arcs as $arc){
			if(strcmp($arc[1], $buffer[0])==0) $canmakeRightArc = 0;
		}
	}else{
		$canmakeRightArc = 0;
	}
	if($canmakeRightArc == 1){ // vai drīkst RIGHTARC?
		$stackCopy = copyStack($stack);
		$bufferCopy = $buffer;
		$arcsCopy = $arcs;
		$movesCopy = $moves;
		
		if(filterArc(array($stackCopy->top(),$bufferCopy[0],"<-R-"), $words, $rules)){
			$movesCopy.=", RIGHT ARC(".$stackCopy->top().", ".$bufferCopy[0].")";
			$arcsCopy[] = array($stackCopy->top(),$bufferCopy[0],"<-R-");
			$stackCopy->push(array_shift($bufferCopy));
			printVars("RIGHT ARC", $stackCopy, $bufferCopy, $arcsCopy, $movesCopy);
			nivre($stackCopy, $bufferCopy, $arcsCopy, $movesCopy);
		}
	}
}

//funkcija steka kopesanai
function copyStack($stack){
	$stackCopy = new SplDoublyLinkedList();
	$stack->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO);
	$stack->rewind();
	while( $stack->valid() )
	{
		$stackCopy->push($stack->current());
		$stack->next();
	}
	$stack->setIteratorMode(SplDoublyLinkedList::IT_MODE_LIFO);
	
	return $stackCopy;
}

//funkcija mainīgo izdrukai
function printVars($action, $stack, $buffer, $arcs, $moves){
	if(count($buffer)==0&&$stack->count()==1){
	global $finalResult;
	if (!array_search($arcs, $finalResult)) {
		$finalResult[] = $arcs;
		foreach ($arcs as $value) {
			echo "(".$value[0].$value[2].$value[1]."), ";
		}
		echo "<br/>";
	}
	//if(1){
		// echo "=========================================<br/>";
		// echo "Move: ".$action."<br/>Stack: ";
		// $stack->rewind();
		// while($stack->valid()) {
			// echo $stack->current().", ";
			// $stack->next();
		// }
		// echo "<br/>Move list: ".$moves;
		// echo "<br/>Buffer: ";
		// foreach ($buffer as $value) {
			// echo $value.", ";
		// }
		// echo "<br/>Arcs: ";
		// foreach ($arcs as $value) {
			// echo "(".$value[0].$value[2].$value[1]."), ";
		// }
		// echo "<br/>=========================================<br/><br/>";
	}
}

//funkcija šķautnes filtresanai
function filterArc($arc, $words, $rules){
	$expStr=explode("_",$arc[1]);
	$Wi=$expStr[1];
	$expStr=explode("_",$arc[0]);
	$Wj=$expStr[1];
	// atrodam vārdšķiru
	foreach($words as $wordArray){
		$wordInfo = array_search($Wi, $wordArray);
		$wordInfoBuff = array_search($Wj, $wordArray);
		if($wordInfo===0){$partOfSpeech = $words[array_search($wordArray, $words)][1];}
		if($wordInfoBuff===0){$partOfSpeechBuff = $words[array_search($wordArray, $words)][1];}
	}
	// atrodam likumu
	$foundRuleCount = 0;
	$foundRules = array();
	foreach($rules as $ruleArray){
		$ruleInfo1 = array_search($partOfSpeech, $ruleArray);
		$ruleInfo2 = array_search($partOfSpeechBuff, $ruleArray);
		if($ruleInfo2===0&&$ruleInfo1===1){
			$wordFunction = $rules[array_search($ruleArray, $rules)][2];
			$arcDirection = $rules[array_search($ruleArray, $rules)][3];
			$foundRules[$foundRuleCount][0]=$wordFunction;
			$foundRules[$foundRuleCount][1]=$arcDirection;
			$foundRuleCount++;
		}
	}
	if($foundRuleCount == 0){
		return 0;
	}else{
		return 1;
	}
}