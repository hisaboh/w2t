<?php
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class JsOneTimeTicketFilter{
	public function flow_handled(Flow $flow){
		if(!$flow->isPost()){
			$flow->vars("_onetimeticket",uniqid("").mt_rand());
			$flow->sessions("_onetimeticket",$flow->inVars("_onetimeticket"));
		}
	}
	public function flow_verify(Flow $flow){
		if(!$flow->isSessions("_onetimeticket") || $flow->inVars("_onetimeticket") !== $flow->inSessions("_onetimeticket")){
			$flow->vars("_onetimeticket",$flow->inSessions("_onetimeticket"));
			throw new Exception("invalid ticket");
		}
	}
	public function before_template(&$src){
		if(Tag::setof($tag,$src,"body")){
			foreach($tag->in("form") as $f){
				if(strtolower($f->inParam("method")) == "post"){
					$func = uniqid("f").mt_rand();
					foreach($f->in("input") as $i){
						if(strtolower($i->inParam("type")) === "submit"){
							$i->param("onclick",$func."(this.form)");
							$f->value(str_replace($i->plain(),$i->get(),$f->value()));
						}
					}
					$f->value("<input type=\"hidden\" name=\"_onetimeticket\" rt:ref=\"false\" />".$f->value());
					$src = str_replace($f->plain(),sprintf('
													<script type="text/javascript"><!--
														function %s(frm){
															frm._onetimeticket.value = "{$_onetimeticket}";
														}
													-->
													</script>',$func).$f->get(),$src);
				}
			}
		}
	}
}
?>