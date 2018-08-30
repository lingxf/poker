<?php
include_once 'debug.php';
include_once 'lib/common.php';
include_once 'db_connect.php';
session_name($web_name);
session_start();
$login_id = isset($_SESSION['user']) ? $_SESSION['user']:'Login';



function split_point($point, $min)
{
	return rand($min, $point - $min);

}

class cboard{
	function __construct(){
		$this->init();
	}
	function __destruct(){
	}

	private $splits = array();
	private $splits_len = array();
	private $poker = array();
	private $poker_big = array();
	private $suit = array(-1,-1,-1,-1);

	function init(){
		for($i = 0; $i < 52; $i++){
			$this->poker[$i] = $i + 2;
		}
		for($i = 0; $i < 4; $i++){
			$this->splits_len[$i] = 0;
		}
	}

	function init_big(){
		for($i = 0; $i < 16; $i++){
			$this->poker_big[$i] = 11 + ($i % 4) + 13 * floor($i/4);
		}
		for($i = 0; $i < 36; $i++){
			$this->poker[$i] = 2 + ($i % 9) + 13*floor($i/9);
		}
		for($i = 36; $i < 52; $i++){
			$this->poker[$i] = 0;
		}
	}

	function mix_big(){
		for($i = 36; $i < 52; $i++){
			$this->poker[$i] = $this->poker_big[$i - 36];
		}
	}

	function swap(&$m, &$n){
		$temp = $m;
		$m = $n;
		$n = $temp;
	}

	public function wash_cards(&$poker, $count=52){
		for($i = 0; $i < $count; $i++){
			$n = rand(0, $count - 1);
			$this->swap($this->poker[$i], $this->poker[$n]);	
			/*
			$temp = $poker[$i];
			$poker[$i] = $poker[$n];
			$poker[$n] = $temp;
			/*
			$temp = $this->poker[$i];
			$this->poker[$i] = $this->poker[$n];
			$this->poker[$n] = $temp;
			*/
		}
		return $this->clean_cards($poker, $count);
	}

	function clean_cards(&$poker, $count=52){
		$last = $count - 1;
		for($i = 0; $i < $count; $i++){
			if($poker[$i] == 0){
				while($poker[$last] == 0 && $last >= 0){
					$last--;
					$count--;
				}
				if($last > $i){
					$poker[$i] = $poker[$last];
					$last--;
					$count--;
				}
			}
		}
		return $count;
	}

	function get_min_big($ppc, $point)
	{
		if($point > 24 && $ppc == 4){
			$m = (1+ floor(($point - 25)/4));
			if($m > 4)
				$m = 4;
			return $m;
		}
		if($point > 12 && $ppc == 3)
			return (1+ floor(($point - 13)/3));
		if($point > 4 && $ppc == 2)
			return (1+ floor(($point - 5)/2));
	}

	function alloc_card(&$big, $point)
	{
		$ppc_array = array(1, 2, 3, 4);
		$card_c = array(0,0,0,0);
		for($i = 3; $i >= 1; $i--){
			$ppc = $ppc_array[$i];
			$max = floor($point / $ppc);
			$max_left_suite = $this->get_max_suit();
			$max = $max > $max_left_suite ? $max_left_suite:$max;
			$min = $this->get_min_big($ppc, $point);
			$n = rand($min, $max);
			$card_c[$i] = $n;
			$point -= $ppc * $n;
			/*
			print("|n:$ppc x $n left:$point|");
			print_r($card_c);
			print("<br>");
			*/
			if($point == 0)
				break;
		}
		//print("final left:$point");
		
		if($point >= 0 && $point <= 4){
			$card_c[$i] = $point;
		}else if($point > 4){
			$card_c[$i] = 4;
			$point -= 4;
			$big = $card_c;
			return $point;
		}
		$big = $card_c;
		return 0;
	}

	function dist_cards(){
		for($i = 0; $i < 52; $i++){
			$this->splits[$i%4][floor($i/4)] = $this->poker[$i];
		}
	}

	function dist_card($player, $card){
		if($this->splits_len[$player] == 13)
			return false;
		$this->splits[$player][$this->splits_len[$player]] = $card;
		$this->splits_len[$player] += 1;
		return true;
	}

	function get_free_suit(){
	}

	function get_in_m(){
		get_free_suit();
	}

	function pick_big($c){
		$i = $c % 13 - 11;
		$color = floor($c/13);
		$this->poker_big[$color * 4 + $i ] = 0;
	}
	
	function pick_small($c){
		$this->poker_small[$c] = 0;
	}

	function get_max_suit(){
		$m = 0;
		for($i = 0; $i < 4; $i++)
			if($this->suit[$i] > 0 || $this->suit[$i] == -1)
				$m++;
		return $m;
	}

	function get_no_suit($j){
		$k = 0;
		for($i = 0; $i < 4; $i++){
			if($this->suit[$i] > 0 || $this->suit[$i] == -1){
				if($k == $j)
					break;
				$k++;
			}
		}
		return $i;
	}
	function get_random_suit($m){
		$n = $this->get_max_suit();
		$arr = array();
		while($m > 0){
			$j = rand(0, $n-1);
			$s = $this->get_no_suit($j);
			$arr[] = $s;
			if($this->suit[$s] != -1)
				$this->suit[$s]--;
			$m--;
			$n--;
		}
		return $arr;
	}

	function alloc_card_to_suit($card, $n){
		$arr = $this->get_random_suit($n);
		foreach($arr as $color){
			$c = $color *13 + $card; 
			$this->pick_big($c);
			$this->dist_card(2, $c);
		}
	}

	function alloc_point($point){
		$ppc_array = array(1, 2, 3, 4);
		for($i = 3; $i >= 1; $i--){
			$ppc = $ppc_array[$i];
			$max = floor($point / $ppc);
			$max_left_suite = $this->get_max_suit();
			$max = $max > $max_left_suite ? $max_left_suite:$max;
			$min = $this->get_min_big($ppc, $point);
			$n = rand($min, $max);
			$this->alloc_card_to_suit(10+$ppc, $n);
			$point -= $ppc * $n;
			if($point == 0)
				break;
		}
		return $point;
	}

	function get_free_space($player){
		return 13 - $this->splits_len[$player];
	}

	function pick_card(&$poker, $cardpoint){
		$color = rand(0, $j-1);
	}

	function get_random_color(&$color_array, $len){
		$p = $len == 1 ? 0 :rand(0, $len - 1);
		$c = $color_array[$p];
		for($i = $p; $i < $len - 1; $i++){
			$color_array[$i] = $color_array[$i+1];
		}
		return $c;
	}

	function fetch_card($point=-1, $ctype){
		if($point == -1){
			$this->wash_cards($this->poker);
			$this->dist_cards();
		}else{
			$isboth = false;
			if($point[0] == '+'){
				$point = intval($point);
				$isboth = true;
			}
			if($point > 40)
				$point = 37;
			$this->init_big();
			$big = array(0,0,0,0);
			$left = $this->alloc_card($big, $point);
			//print("left:$left<br>");
			$di = 0;
			for($i = 0; $i < 4; $i++){
				$di += $big[$i];
			}
			if($isboth){
				$north = rand(0, $di - 1);
			}
			for($i = 0; $i < 4; $i++){
				$color_array = array(0, 1, 2, 3);
				for($j = 0; $j < $big[$i]; $j++){
					$color = $this->get_random_color($color_array, 4-$j);
					$c = 11+$i+13*$color;	
					$this->poker_big[$color * 4 + $i ] = 0;

					if($isboth){
						$w = rand(0, 1);
						if($w == 0)
							$this->dist_card(0, $c);
						else
							$this->dist_card(2, $c);
					}else{
						$this->dist_card(2, $c);
					}
				}
			}

			$count = $this->wash_cards($this->poker, 36);
			$free = $this->get_free_space(2);
			for($i = 0; $i < $free; $i++){
				$c = $this->poker[35 - $i];
				$this->poker[35 - $i ] = 0;
				$this->dist_card(2, $c);
			}
			if($isboth){
				$free0 = $this->get_free_space(0);
				for($i = 0; $i < $free0; $i++){
					$c = $this->poker[35 - $free - $i];
					$this->poker[35 - $free - $i ] = 0;
					$this->dist_card(0, $c);
				}
			}

			$this->mix_big();
			$count = $this->wash_cards($this->poker);
			if($count != 39 && $count != 26){
				print("Wrong:$count");
			}
			if($isboth){
				for($i = 0; $i < $count; $i+=2){
					$this->dist_card(1, $this->poker[$i+1]);
					$this->dist_card(3, $this->poker[$i+2]);
				}
			}else{
				for($i = 0; $i < $count; $i+=3){
					$this->dist_card(0, $this->poker[$i]);
					$this->dist_card(1, $this->poker[$i+1]);
					$this->dist_card(3, $this->poker[$i+2]);
				}
			}
		}
		return $this->splits;
	}

	function wash_card1(&$cards)
	{
		$map = array(0, 1, 2, 3);
		$len = array(0, 0, 0, 0);
		$max = 52;
		$mlen = 3;
		for($i = 2; $i <= 53; $i++){
			$m = $mlen == 0 ? 0 : ( rand(0, $mlen));
			$cards[$map[$m]][$len[$m]] = $i;
			$len[$m] += 1;
			if($len[$m] == 13){
				if($mlen == 0)
					break;
				$mlen -= 1;
				for($j = $m; $j < 3; $j++){
					$map[$j] = $map[$j+1];
					$len[$j] = $len[$j+1];
				}
			}
		}
	}
}

function card_gen(&$cards, $point=-1, $ctype=0)
{
	$cc = new cboard();
	$cards = $cc->fetch_card($point, $ctype);
	//print("Point:$point");
}

function check_card_color($n, $color)
{
	$cc = array('S'=>0, 'H'=>1, 'D'=>2, 'C'=>3);
	$c = $cc[$color];
	$n = $n - 2;
	if(floor($n/13) == $c)
		return True ;
	else
		return False;
}

function print_cardline($card, $color, $show=1)
{
	print("<td>");

	print("[$color]  ");
	if($show == 0){
		print("X X X X");
		return;	
	}
	rsort($card);
	for($i = 0; $i < 13; $i++){
		if(check_card_color($card[$i], $color)){
			$ca = ($card[$i]-2) % 13 + 2;
			if($ca <= 10 && $ca >= 2 )
				print($ca); 
			else{	
				if($ca == 11)
					print('J');
				else if($ca == 12)
					print('Q');
				else if($ca == 13)
					print('K');
				else if($ca == 14)
					print('A');
				}
			print(" ");
		}
	}
	print("</td>");
}

function print_card($cards, $show_pos)
{
	$pos0 = $show_pos & 1;
	$pos1 = $show_pos & 2;
	$pos2 = $show_pos & 4;
	$pos3 = $show_pos & 8;
	print("<table>");
	print("<tr>");
	print("<td rowspan=4></td>");
	print_cardline($cards[0], 'S', $pos0);
	print("<td rowspan=4></td>");
	print("</tr>");
	print("<tr>");
	print_cardline($cards[0], 'H', $pos0);
	print("</tr>");
	print("<tr>");
	print_cardline($cards[0], 'D', $pos0);
	print("</tr>");
	print("<tr>");
	print_cardline($cards[0], 'C', $pos0);
	print("</tr>");

	print("<tr>");
	print_cardline($cards[1], 'S', $pos1);
	print("<td rowspan=4></td>");
	print_cardline($cards[3], 'S', $pos3);
	print("</tr>");

	print("<tr>");
	print_cardline($cards[1], 'H', $pos1);
	print_cardline($cards[3], 'H', $pos3);
	print("</tr>");

	print("<tr>");
	print_cardline($cards[1], 'D', $pos1);
	print_cardline($cards[3], 'D', $pos3);
	print("</tr>");
	print("<tr>");
	print_cardline($cards[1], 'C', $pos1);
	print_cardline($cards[3], 'C', $pos3);
	print("</tr>");

	print("<tr>");
	print("<td rowspan=4></td>");
	print_cardline($cards[2], 'S', $pos2);
	print("<td rowspan=4></td>");
	print("</tr>");
	print("<tr>");
	print_cardline($cards[2], 'H', $pos2);
	print("</tr>");
	print("<tr>");
	print_cardline($cards[2], 'D', $pos2);
	print("</tr>");
	print("<tr>");
	print_cardline($cards[2], 'C', $pos2);
	print("</tr>");
	print("</table>");
}

function get_stock_card(&$cards, $cardno)
{
	if($cardno != -1)
		$sql = "select * from cards where no = $cardno";
	else
		$sql = "select * from cards order by no desc limit 1";
	$res = read_mysql_query($sql);
	while($row = mysql_fetch_array($res)){
		$cards = unserialize($row['cardstr']);
		return true;
	}
	return false;
}

function set_stock_card($cards) 
{
	$str = serialize($cards);
	$sql = "insert into cards set cardstr = '$str'";
	$res = update_mysql_query($sql);
	$sql = "select * from cards where cardstr = '$str'";
	$res = read_mysql_query($sql);
	while($row = mysql_fetch_array($res)){
		$no = $row['no'];
		print("$no");
		return true;
	}
	return false;
}

$card_test = array(
	array( 0),
	array( 0),
	array( 0),
	array( 0),
);

$action = get_url_var('action', 'new');
$point = get_url_var('point', -1);
$ctype = get_url_var('ctype', 0);
$show_pos = get_url_var('pos', 4);
$show_stock = get_url_var('show_stock', false);
$cardno = get_url_var('cardno', -1);
if($point == '')
	$point = -1;

switch($action){
	case 'new':
		card_gen($card_test, $point, $ctype);
		$_SESSION['card'] = $card_test;
		print_card($card_test, $show_pos);
		break;
	case 'save':
		$card_test = $_SESSION['card'];
		set_stock_card($card_test);
		break;
	case 'refresh':
		if($show_stock)
			get_stock_card($card_test, $cardno);	
		else{
			if(isset($_SESSION['card']))
				$card_test = $_SESSION['card'];
			else
				card_gen($card_test, $point, $ctype);
		}
		print_card($card_test, $show_pos);
		break;
}
?>
