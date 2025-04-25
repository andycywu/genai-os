set "EXECUTOR_ACCESS_CODE="doc_qa --exclude=web_qa""
pushd ..\..\..\src\multi-chat
php artisan model:config "web_qa" "Web QA" --image "..\..\windows\executors\docQA & webQA\webQA.png"
php artisan model:config "doc_qa" "Document QA" --image "..\..\windows\executors\docQA & webQA\docQA.png"
popd
pushd ..\..\..\src\executor\docqa
start /b "" "python" "docqa.py" "--access_code" "web_qa" "doc_qa" --model taide --mmr_k 6 --mmr_fetch_k 12 --limit 3072
popd
