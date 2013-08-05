<?php
global $last_postfix;

function test_postfix($postfix) {
	global $last_postfix;
	if($postfix > $last_postfix) {
		return true; // It's free.
	} else {
		return false;
	}
}

function determine_postfix() {
	$postfix = null;
	$lower_postfix = 0;
	$upper_postfix = 1;
	// Find an upper-bound for the postfix - exponentially.
	while(test_postfix($upper_postfix) == false) {
		$upper_postfix *= 2;
	}
	echo "lower_postfix = $lower_postfix\n";
	echo "upper_postfix = $upper_postfix\n";
	$iteration = 0;
	while($upper_postfix - $lower_postfix > 1) {
		$middle_postfix = floor($upper_postfix-$lower_postfix)/2 + $lower_postfix;
		echo "middle_postfix = $middle_postfix\n";
		if(test_postfix($middle_postfix)) {
			$upper_postfix = $middle_postfix;
		} else {
			$lower_postfix = $middle_postfix;
		}
		$iteration++;
	}
	return $upper_postfix;
}

function test($N) {
	global $last_postfix;
	for($n = 1; $n <= $N; $n++) {
		$last_postfix = rand(1, 9999);
		echo "Test #$n ($last_postfix).\n";
		if(determine_postfix() == $last_postfix + 1) {
			echo "Success!\n";
		} else {
			throw new Exception("Test #$n: Failed!");
		}
	}
}
test(1000);
