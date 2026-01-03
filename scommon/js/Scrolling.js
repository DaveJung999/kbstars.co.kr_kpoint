							function HanaScl(){
								this.version = "0.1";
								this.name = "HanaScl";
								this.item = new Array();
								this.itemcount = 0;
								this.currentspeed = 50;
								this.scrollspeed = 50;
								this.pausedelay = 1000;
								this.pausemouseover = false;
								this.stop = false;
								this.type = 1;
								this.height = 40;
								this.width = 200;
								this.stopHeight=0;
								this.add =	function () {
													var text = arguments[0];
													this.item[this.itemcount] = text;
													this.itemcount = this.itemcount + 1;
												};
								this.start =	function () {
													this.display();
													this.currentspeed = this.scrollspeed;
													setTimeout(this.name+'.scroll()',this.currentspeed);
												};
								this.display =	function () {
														document.write('<div id=full><div id="'+this.name+'" style="height:'+this.height+';width:'+this.width+';position:relative;overflow:hidden;" OnMouseOver="'+this.name+'.onmouseover();" OnMouseOut="'+this.name+'.onmouseout();">');
														
														for(var i = 0; i < this.itemcount; i++) {
															if ( this.type == 1) {
																document.write('<div id="'+this.name+'item'+i+'" style="left:0px;width:'+this.width+';position:absolute;top:'+(this.height*i+1)+'px;">');
																document.write(this.item[i]);
																document.write('</div>');
															} else if ( this.type == 2 ) {
																document.write('<div id="'+this.name+'item'+i+'" style="left:'+(this.width*i+10)+'px;width:'+this.width+';position:absolute;top:0px;">');
																document.write(this.item[i]);
																document.write('</div>');
															}
														}
														document.write('</div></div>');
													};
								this.scroll =	function () {
														this.currentspeed = this.scrollspeed;
														if ( !this.stop ) {
															for (i = 0; i < this.itemcount; i++) {
																obj = document.getElementById(this.name+'item'+i).style;
																if ( this.type == 1 ) {
																	obj.top = parseInt(obj.top) - 1;
																	if ( parseInt(obj.top) <= this.height*(-1) )
																		obj.top = this.height * (this.itemcount-1);
//																		a5.value = obj.top;
																	if ( parseInt(obj.top) == 0 || ( this.stopHeight > 0 && this.stopHeight - parseInt(obj.top) == 0 ) )
																		this.currentspeed = this.pausedelay;
																} else if ( this.type == 2 ) {
																		obj.left = parseInt(obj.left) - 10;
																		if ( parseInt(obj.left) <= this.width*(-1) )
																			obj.left = this.width* (this.itemcount-1);
																		if ( parseInt(obj.left) == 0 )
																			this.currentspeed = this.pausedelay;
																}
															}
														}
														window.setTimeout(this.name+".scroll()",this.currentspeed);
													};
								this.onmouseover =		function () {
																	if ( this.pausemouseover ) {
																		this.stop = true;
																	}
																};
								this.onmouseout =		function () {
																	if ( this.pausemouseover ) {
																		this.stop = false;
																	}
																};
							}