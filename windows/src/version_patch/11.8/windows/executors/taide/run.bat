set EXECUTOR_ACCESS_CODE=taide
pushd ..\..\..\src\multi-chat
php artisan model:config "taide" "LLaMA3 TAIDE LX-8B Chat Alpha1 4bit" --image "..\..\windows\executors\taide\TAIDE.png"
popd
start /b "" "kuwa-executor" "llamacpp" "--access_code" "taide" "--model_path" "taide-8b-a.3-q4_k_m.gguf" "--ngl" "-1" "--stop" "<|eot_id|>" "--system_prompt" "�A�O�@�ӨӦۥx�W��AI�U�z�A�A���W�r�O TAIDE�A�֩�H�x�W�H���߳����U�ϥΪ̡A�|���c�餤��^�����D�C"
