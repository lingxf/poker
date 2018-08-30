import operator  

def c(n,k):  
	if k == 0:
		return 1
	return  reduce(operator.mul, range(n - k + 1, n + 1)) /reduce(operator.mul, range(1, k +1))  
   
def fac(n):  
	return reduce(operator.mul, range(1,n+1))	
   
ct = { }
def add_up(comb, val):
	comb = sorted(list(comb), reverse = True)
	comb = tuple(comb)
	if ct.has_key(comb):
		ct[comb] = ct[comb] + val
	else:
		ct[comb] = val

def match(total, comb):
	if comb[0] + comb[1] == total:
		return (comb[2], comb[3])
	if comb[0] + comb[2] == total:
		return (comb[1], comb[3])
	if comb[0] + comb[3] == total:
		return (comb[1], comb[2])
	if comb[1] + comb[2] == total:
		return (comb[0], comb[3])
	if comb[1] + comb[3] == total:
		return (comb[0], comb[2])
	if comb[2] + comb[3] == total:
		return (comb[0], comb[1])
	return None

bid_a = {}
def bid_add(bid, suit_count, total):
	if bid_a.has_key(bid):
		if bid_a[bid].has_key(suit_count):
			bid_a[bid][suit_count] = bid_a[bid][suit_count] + total
		else:
			bid_a[bid][suit_count] = total
		bid_a[bid][0] += total
	else:
		bid_a[bid] = {}
		bid_a[bid][0] = total
		bid_a[bid][suit_count] = total

def bid_check(comb, count): 
	i,j,k,l = comb
	if i < 5 and j < 5 and (l > k or (l == k and l == 3)):
		bid_add('C1', l, count)
	elif i < 5 and j < 5 and (k > l or (l == k and l < 4)):
		bid_add('D1', k, count)
	elif i >= 5 and i >= j:
		bid_add('S1', i, count)
	elif j >= 5:
		bid_add('H1', j, count)

bt = {}
def cond_cal(comb, val):
	for add_total in (2, 3, 4, 5, 6, 7, 8, 9, 10, 11):
		cc = match(add_total, comb)
		if cc != None:
			if bt.has_key(add_total):
				dd = bt[add_total]
				if dd.has_key(cc):
					dd[cc] = dd[cc] + val
				else:
					dd[cc] = val
				bt[add_total] = dd
			else:
				bt[add_total] = {cc:val}


total = c(52, 13)
print total
for i in xrange(14):
	for j in xrange(0, 14-i):
		for k in xrange(0, 14-j-i):
			l = 13-k-j-i
			value = c(13, i)*c(13, j)*c(13, k)*c(13, l)
			add_up((i, j, k, l), value)
			bid_check((i,j,k,l), value)

ctt = sorted(ct.iteritems(), key=lambda d:d[1], reverse = True)
for cb, v in ctt:
	print("{0[0]:2} {0[1]} {0[2]} {0[3]} : {1:<08.6%}".format(cb, v*1.0/total))

for cb, v in ctt:
	cond_cal(cb, v)

print("===============================")
for tt, dd in bt.iteritems():
	tot = 0
	for comb, v in dd.iteritems():
		tot = tot + v
	for comb, v in dd.iteritems():
		print("{0:2} : {1[0]} {1[1]} : {2:<08.6%}".format(tt, comb, v*1.0/tot))
	print("-----------------")

print("===============================")
for bid, suit in bid_a.iteritems():
	for i in xrange(3, 10):
		if bid_a[bid].has_key(i):
			print("[{}] {}:{:<04.2%}".format(bid, i, suit[i]*1.0/suit[0]))
