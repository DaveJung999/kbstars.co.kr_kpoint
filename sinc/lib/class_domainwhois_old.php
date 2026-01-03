<?php
/**
* This class checks the availability of a domain and gets the whois data
*
* @author	Sven Wagener <sven.wagener@intertribe.de>
* @copyright inDot media
* @include 	 Funktion:_include_
*/

class domain{
	var $domain="";
	
	/*******************************
	* Initializing server variables
	* array(top level domain,whois_Server,not_found_string or MAX number of CHARS: MAXCHARS:n)
	**/
	var $servers=array(
	array("ac","whois.nic.ac","No match"),
	array("ac.cn","whois.cnnic.net.cn","no matching record"),
	array("ac.jp","whois.nic.ad.jp","No match"),
	array("ac.uk","whois.ja.net","No such domain"),
	array("ad.jp","whois.nic.ad.jp","No match"),
	array("adm.br","whois.nic.br","No match"),
	array("adv.br","whois.nic.br","No match"),
	array("aero","whois.information.aero","is available"),
	array("ag","whois.nic.ag","does not exist"),
	array("agr.br","whois.nic.br","No match"),
	array("ah.cn","whois.cnnic.net.cn","No entries found"),
	array("al","whois.ripe.net","No entries found"),
	array("am","whois.amnic.net","No match"),
	array("am.br","whois.nic.br","No match"),
	array("arq.br","whois.nic.br","No match"),
	array("at","whois.nic.at","nothing found"),
	array("au","whois.aunic.net","No Data Found"),
	array("art.br","whois.nic.br","No match"),
	array("as","whois.nic.as","Domain Not Found"),
	array("asn.au","whois.aunic.net","No Data Found"),
	array("ato.br","whois.nic.br","No match"),
	array("az","whois.ripe.net","no entries found"),
	array("ba","whois.ripe.net","No Match for"),
	array("be","whois.geektools.com","No such domain"),
	array("bg","whois.digsys.bg","does not exist"),
	array("bio.br","whois.nic.br","No match"),
	array("biz","whois.biz","Not found"),
	array("bj.cn","whois.cnnic.net.cn","No entries found"),
	array("bmd.br","whois.nic.br","No match"),
	array("br","whois.registro.br","No match"),
	array("by","whois.ripe.net","no entries found"),
	array("ca","whois.cira.ca","Status: AVAIL"),
	array("cc","whois.nic.cc","No match"),
	array("cd","whois.cd","No match"),
	array("ch","whois.nic.ch","We do not have an entry"),
	array("cim.br","whois.nic.br","No match"),
	array("ck","whois.ck-nic.org.ck","No entries found"),
	array("cl","whois.nic.cl","no existe"),
	array("cn","whois.cnnic.net.cn","No entries found"),
	array("cng.br","whois.nic.br","No match"),
	array("cnt.br","whois.nic.br","No match"),
	array("com","whois.crsnic.net","No match"),
	array("com.au","whois.aunic.net","No Data Found"),
	array("com.br","whois.nic.br","No match"),
	array("com.cn","whois.cnnic.net.cn","No entries found"),
	array("com.eg","whois.ripe.net","No entries found"),
	array("com.hk","whois.hknic.net.hk","No Match for"),
	array("com.mx","whois.nic.mx","Nombre del Dominio"),
	array("com.ru","whois.ripn.ru","No entries found"),
	array("com.tw","whois.twnic.net","NO MATCH TIP"),
	array("conf.au","whois.aunic.net","No entries found"),
	array("co.jp","whois.nic.ad.jp","No match"),
	array("co.uk","whois.nic.uk","No match for"),
	array("cq.cn","whois.cnnic.net.cn","No entries found"),
	array("csiro.au","whois.aunic.net","No Data Found"),
	array("cx","whois.nic.cx","No match"),
	array("cy","whois.ripe.net","no entries found"),
	array("cz","whois.nic.cz","No data found"),
	array("de","whois.denic.de","No entries found"),
	array("dk","whois.dk-hostmaster.dk","No entries found"),
	array("dz","whois.ripe.net","no entries found"),
	array("ecn.br","whois.nic.br","No match"),
	array("ee","whois.eenet.ee","NOT FOUND"),
//	array("edu","whois.verisign-grs.net","No match"),
	array("edu","whois.crsnic.net","No match"),
	array("edu.au","whois.aunic.net","No Data Found"),
	array("edu.br","whois.nic.br","No match"),
	array("eg","whois.ripe.net","No entries found"),
	array("es","whois.ripe.net","No entries found"),
	array("esp.br","whois.nic.br","No match"),
	array("etc.br","whois.nic.br","No match"),
	array("eti.br","whois.nic.br","No match"),
	array("eun.eg","whois.ripe.net","No entries found"),
	array("emu.id.au","whois.aunic.net","No Data Found"),
	array("eng.br","whois.nic.br","No match"),
	array("far.br","whois.nic.br","No match"),
	array("fi","whois.ripe.net","No entries found"),
	array("fj","whois.usp.ac.fj",""),
	array("fj.cn","whois.cnnic.net.cn","No entries found"),
	array("fm.br","whois.nic.br","No match"),
	array("fnd.br","whois.nic.br","No match"),
	array("fo","whois.ripe.net","no entries found"),
	array("fot.br","whois.nic.br","No match"),
	array("fst.br","whois.nic.br","No match"),
	array("fr","whois.nic.fr","No entries found"),
	array("gb","whois.ripe.net","No match for"),
	array("gb.com","whois.nomination.net","No match for"),
	array("gb.net","whois.nomination.net","No match for"),
	array("g12.br","whois.nic.br","No match"),
	array("gd.cn","whois.cnnic.net.cn","No entries found"),
	array("ge","whois.ripe.net","no entries found"),
	array("ggf.br","whois.nic.br","No match"),
	array("gl","whois.ripe.net","no entries found"),
	array("gr","whois.ripe.net","no entries found"),
	array("gr.jp","whois.nic.ad.jp","No match"),
	array("gs","whois.adamsnames.tc","is not registered"),
	array("gs.cn","whois.cnnic.net.cn","No entries found"),
	array("gov.au","whois.aunic.net","No Data Found"),
	array("gov.br","whois.nic.br","No match"),
	array("gov.cn","whois.cnnic.net.cn","No entries found"),
	array("gov.hk","whois.hknic.net.hk","No Match for"),
	array("gob.mx","whois.nic.mx","Nombre del Dominio"),
	array("gs","whois.adamsnames.tc","is not registered"),
	array("gz.cn","whois.cnnic.net.cn","No entries found"),
	array("gx.cn","whois.cnnic.net.cn","No entries found"),
	array("he.cn","whois.cnnic.net.cn","No entries found"),
	array("ha.cn","whois.cnnic.net.cn","No entries found"),
	array("hb.cn","whois.cnnic.net.cn","No entries found"),
	array("hi.cn","whois.cnnic.net.cn","No entries found"),
	array("hl.cn","whois.cnnic.net.cn","No entries found"),
	array("hn.cn","whois.cnnic.net.cn","No entries found"),
	array("hm","whois.registry.hm","(null)"),
	array("hk","whois.hknic.net.hk","No Match for"),
	array("hk.cn","whois.cnnic.net.cn","No entries found"),
	array("hu","whois.ripe.net","MAXCHARS:500"),
	array("id.au","whois.aunic.net","No Data Found"),
	array("ie","whois.domainregistry.ie","no match"),
	array("ind.br","whois.nic.br","No match"),
	array("imb.br","whois.nic.br","No match"),
	array("inf.br","whois.nic.br","No match"),
	array("info","whois.afilias.info","Not found"),
	array("info.au","whois.aunic.net","No Data Found"),
	array("it","whois.nic.it","No entries found"),
	array("idv.tw","whois.twnic.net","NO MATCH TIP"),
	array("int","whois.iana.org","not found"),
	array("is","whois.isnic.is","No entries found"),
	array("il","whois.isoc.org.il","No data was found"),
	array("jl.cn","whois.cnnic.net.cn","No entries found"),
	array("jor.br","whois.nic.br","No match"),
	array("jp","whois.nic.ad.jp","No match"),
	array("js.cn","whois.cnnic.net.cn","No entries found"),
	array("jx.cn","whois.cnnic.net.cn","No entries found"),
	array("ke","whois.rg.net","No match for"),
	array("kr","whois.krnic.net","is not registered"),
	array("la","whois.nic.la","NO MATCH"),
	array("lel.br","whois.nic.br","No match"),
	array("li","whois.nic.ch","We do not have an entry"),
	array("lk","whois.nic.lk","No domain registered"),
	array("ln.cn","whois.cnnic.net.cn","No entries found"),
	array("lt","ns.litnet.lt","No matches found"),
	array("lu","whois.dns.lu","No entries found"),
	array("lv","whois.ripe.net","no entries found"),
	array("ltd.uk","whois.nic.uk","No match for"),
	array("ma","whois.ripe.net","No entries found"),
	array("mat.br","whois.nic.br","No match"),
	array("mc","whois.ripe.net","No entries found"),
	array("md","whois.ripe.net","No match for"),
	array("me.uk","whois.nic.uk","No match for"),
	array("med.br","whois.nic.br","No match"),
	array("mil","whois.nic.mil","No match"),
	array("mil.br","whois.nic.br","No match"),
	array("mk","whois.ripe.net","No match for"),
	array("mn","whois.nic.mn","Domain not found"),
	array("mo.cn","whois.cnnic.net.cn","No entries found"),
	array("ms","whois.adamsnames.tc","is not registered"),
	array("mt","whois.ripe.net","No Entries found"),
	array("mus.br","whois.nic.br","No match"),
	array("mx","whois.nic.mx","Nombre del Dominio"),
	array("name","whois.nic.name","No match"),
	array("ne.jp","whois.nic.ad.jp","No match"),
	array("net","whois.crsnic.net","No match"),
	array("net.au","whois.aunic.net","No Data Found"),
	array("net.br","whois.nic.br","No match"),
	array("net.cn","whois.cnnic.net.cn","No entries found"),
	array("net.eg","whois.ripe.net","No entries found"),
	array("net.hk","whois.hknic.net.hk","No Match for"),
	array("net.lu","whois.dns.lu","No entries found"),
	array("net.mx","whois.nic.mx","Nombre del Dominio"),
	array("net.uk","whois.nic.uk","No match for "),
	array("net.ru","whois.ripn.ru","No entries found"),
	array("net.tw","whois.twnic.net","NO MATCH TIP"),
	array("nl","whois.domain-registry.nl","is not a registered domain"),
	array("nm.cn","whois.cnnic.net.cn","No entries found"),
	array("no","whois.norid.no","no matches"),
	array("no.com","whois.nomination.net","No match for"),
	array("nom.br","whois.nic.br","No match"),
	array("not.br","whois.nic.br","No match"),
	array("ntr.br","whois.nic.br","No match"),
	array("nu","whois.nic.nu","NO MATCH for"),
	array("nx.cn","whois.cnnic.net.cn","No entries found"),
	array("nz","whois.domainz.net.nz","Not Listed"),
	array("plc.uk","whois.nic.uk","No match for"),
	array("odo.br","whois.nic.br","No match"),
	array("oop.br","whois.nic.br","No match"),
	array("or.jp","whois.nic.ad.jp","No match"),
	array("org","whois.pir.org","No match"),
	array("org.au","whois.aunic.net","No Data Found"),
	array("org.br","whois.nic.br","No match"),
	array("org.cn","whois.cnnic.net.cn","No entries found"),
	array("org.hk","whois.hknic.net.hk","No Match for"),
	array("org.lu","whois.dns.lu","No entries found"),
	array("org.ru","whois.ripn.ru","No entries found"),
	array("org.tw","whois.twnic.net","NO MATCH TIP"),
	array("org.uk","whois.nic.uk","No match for"),
	array("pk","whois.pknic.net","is not registered"),
	array("pl","whois.ripe.net","No information about"),
	array("pp.ru","whois.ripn.ru","No entries found"),
	array("ppg.br","whois.nic.br","No match"),
	array("pro.br","whois.nic.br","No match"),
	array("psi.br","whois.nic.br","No match"),
	array("psc.br","whois.nic.br","No match"),
	array("pt","whois.ripe.net","No match for"),
	array("qh.cn","whois.cnnic.net.cn","No entries found"),
	array("qsl.br","whois.nic.br","No match"),
	array("rec.br","whois.nic.br","No match"),
	array("ro","whois.ripe.net","No entries found"),
	array("ru","whois.ripn.ru","No entries found"),
	array("sc.cn","whois.cnnic.net.cn","No entries found"),
	array("sd.cn","whois.cnnic.net.cn","No entries found"),
	array("se","whois.iis.se","not found"),
	array("se.com","whois.centralnic.com","No match for"),
	array("sec.ps","whois.pnina.ps","No entries found"),
	array("sg","whois.nic.net.sg","Domain Not Found"),
	array("sh.cn","whois.cnnic.net.cn","No entries found"),
	array("sh","whois.nic.sh","No match"),
	array("shop.pl","whois.dns.pl","No data found"),
	array("si","whois.arnes.si","No entries found"),
	array("sk","whois.sk-nic.sk","No entries found"),
	array("sl","whois.ripe.net","No entries found"),
	array("sn.cn","whois.cnnic.net.cn","No entries found"),
	array("so","whois.nic.so","No match"),
	array("sp.cn","whois.cnnic.net.cn","No entries found"),
	array("sr","whois.registry.sr","No entries found"),
	array("srl.br","whois.nic.br","No match"),
	array("su","whois.ripn.ru","No entries found"),
	array("sv","whois.ripe.net","No entries found"),
	array("sx.cn","whois.cnnic.net.cn","No entries found"),
	array("sx","whois.ripe.net","No entries found"),
	array("tc","whois.adamsnames.tc","is not registered"),
	array("td","whois.nic.td","No entries found"),
	array("teo.br","whois.nic.br","No match"),
	array("tf","whois.nic.tf","No entries found"),
	array("th","whois.thnic.co.th","No entries found"),
	array("tj","whois.nic.tj","No entries found"),
	array("tk","whois.dot.tk","No entries found"),
	array("tm","whois.nic.tm","No entries found"),
	array("tn","whois.ripe.net","No entries found"),
	array("to","whois.tonic.to","No entries found"),
	array("tr","whois.nic.tr","No entries found"),
	array("trd.br","whois.nic.br","No match"),
	array("ts.cn","whois.cnnic.net.cn","No entries found"),
	array("tt","whois.nic.tt","No match"),
	array("tv","whois.tv","No match"),
	array("tw","whois.twnic.net","NO MATCH TIP"),
	array("tz","whois.ripe.net","No entries found"),
	array("ud.cn","whois.cnnic.net.cn","No entries found"),
	array("uk","whois.nic.uk","No match for"),
	array("uk.com","whois.nomination.net","No match for"),
	array("uk.net","whois.nomination.net","No match for"),
	array("uy","whois.nic.org.uy","No hay registros"),
	array("uz","whois.cctld.uz","No entries found"),
	array("vn","whois.vnnic.vn","No entries found"),
	array("vu","whois.ripe.net","No entries found"),
	array("wf","whois.nic.wf","No entries found"),
	array("whoswho","whois.nic.whoswho","No entries found"),
	array("ws","whois.website.ws","No match"),
	array("ws.cn","whois.cnnic.net.cn","No entries found"),
	array("xj.cn","whois.cnnic.net.cn","No entries found"),
	array("xz.cn","whois.cnnic.net.cn","No entries found"),
	array("yn.cn","whois.cnnic.net.cn","No entries found"),
	array("yu","whois.ripe.net","No entries found"),
	array("za","whois.co.za","No match"),
	array("zj.cn","whois.cnnic.net.cn","No entries found"));

	var $idn = array(224,225,226,227,228,229,230,231,232,233,234,235,240,236,237,238,239,241,242,243,244,245,246,248,254,249,250,251,252,253,255);
	
	/**
	* Constructor of class domain
	* @param string $str_domainname the full name of the domain
	*/
	function __construct($str_domainname){
		$this->domain=$str_domainname;
	}
	
	/**
	* Returns the whois data of the domain
	* @return string Whois data as string
	*/
	function info(){
		if($this->is_valid()){
			$tldname=$this->get_tld();
			$domainname=$this->get_domain();
			$whois_server=$this->get_whois_server();

			if($whois_server != ""){
				$fp = @fsockopen($whois_server, 43);
				if (!$fp){
					return "Could not connect to whois server!";
				}
				$dom=$domainname.".".$tldname;
				fputs($fp, "{$dom}\r\n");
				
				$string="";
				if($tldname == "com" or $tldname == "net" or $tldname == "edu"){
					while(!feof($fp)){
						$line=trim(fgets($fp,128));
						$string.=$line;
						$lineArr=explode(":",$line);
						if(strtolower($lineArr['0']) == "whois server"){
							$whois_server=trim($lineArr['1']);
						}
					}
					fclose($fp);
					
					$fp = @fsockopen($whois_server, 43);
					if (!$fp){
						return "Could not connect to secondary whois server!";
					}
					fputs($fp, "{$dom}\r\n");
					$string="";
					
					while(!feof($fp)){
						$string.=fgets($fp,128);
					}
				} else {
					while(!feof($fp)){
						$string.=fgets($fp,128);
					}
				}
				fclose($fp);
				return $string;
			} else {
				return "No whois server for this tld in list!";
			}
		} else {
			return "Domain name isn't valid!";
		}
	}
	
	/**
	* Returns the whois data of the domain in HTML format
	*/
	function html_info(){
		return nl2br(htmlspecialchars($this->info()));
	}
	
	/**
	* Returns name of the whois server of the tld
	*/
	function get_whois_server(){
		$tldname=$this->get_tld();
		$server="";
		foreach($this->servers as $item){
			if($item['0'] == $tldname){
				$server=$item['1'];
				break;
			}
		}
		return $server;
	}
	
	/**
	* Returns the tld of the domain without domain name
	*/
	function get_tld(){
		$domainArr=explode(".",$this->domain);
		if(count($domainArr)>2){
			return implode(".",array_slice($domainArr,1));
		} else {
			return $domainArr['1'];
		}
	}
	
	/**
	* Returns all tlds which are supported by the class
	*/
	function get_tlds(){
		$tlds=array();
		foreach($this->servers as $server){
			$tlds[]=$server['0'];
		}
		return $tlds;
	}
	
	/**
	* Returns the name of the domain without tld
	*/
	function get_domain(){
		$domainArr=explode(".",$this->domain);
		return $domainArr['0'];
	}
	
	/**
	* Returns the string which will be returned by the whois server of the tld if a domain is available
	*/
	function get_notfound_string(){
		$tldname=$this->get_tld();
		$notfound="";
		foreach($this->servers as $item){
			if($item['0'] == $tldname){
				$notfound=$item['2'];
				break;
			}
		}
		return $notfound;
	}
	
	/**
	* Returns if the domain is available for registering
	*/
	function is_available(){
		$whois_string=$this->info();
		$not_found_string=$this->get_notfound_string();
		$domain=$this->domain;
		
		$whois_string2=str_ireplace($domain,"",$whois_string);
		
		$whois_string2=preg_replace("/\s+/"," ",$whois_string2);
		
		$array=explode(":",$not_found_string);
		
		if($array['0'] == "MAXCHARS"){
			return strlen($whois_string2)<=intval($array['1']);
		} else {
			return preg_match("/".preg_quote($not_found_string,"/") . "/i",$whois_string2);
		}
	}
	
	/**
	* Returns if the domain name is valid
	*/
	function is_valid(){
		$domainArr=explode(".",$this->domain);
		
		if(count($domainArr) == 3){
			$tld=$domainArr['1'].".".$domainArr['2'];
			$found=false;
			foreach($this->servers as $server){
				if($server['0'] == $tld){
					$found=true;
					break;
				}
			}
			if(!$found){
				return false;
			}
		} elseif (count($domainArr)>3){
			return false;
		}
		
		if($this->get_tld() == "de"){
			$idn="";
			foreach($this->idn as $char){
				$idn.=chr($char);
			}
			$pattern="/^[a-z".$idn."0-9\-]{3,}$/i";
		} else {
			$pattern="/^[a-z0-9\-]{3,}$/i";
		}
		$domainPart=strtolower($this->get_domain());
		if(preg_match($pattern,$domainPart) and !preg_match("/--/",$domainPart)){
			return true;
		} else {
			return false;
		}
	}
}
?>
