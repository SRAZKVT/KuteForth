hook global BufCreate .*[.]kf %{
	set-option buffer filetype kuteforth
}

addhl shared/kuteforth regions
addhl shared/kuteforth/double-string region '"' '"' fill string
addhl shared/kuteforth/single-string region "'" "'" fill value
addhl shared/kuteforth/comment region '//' '$' fill comment
addhl shared/kuteforth/code default-region group

addhl shared/kuteforth/code/type regex '\b(?:bool|int|ptr|void)\b' 0:type
addhl shared/kuteforth/code/stackops regex '\b(?:swap|drop|over|rot|dup)\b' 0:function
addhl shared/kuteforth/code/keywords regex '\b(?:func|in|end|if|do|else|elif|while|mem_start|pwrite|pread|argc|argv|include)\b' 0:keyword

hook -group kuteforth-highlight global WinSetOption filetype=kuteforth %{
	add-highlighter window/kuteforth ref kuteforth
	hook -once -always window WinSetOption filetype=.* %{ remove-highlighter window/kuteforth }
}
