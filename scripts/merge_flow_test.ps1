$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$r = Invoke-WebRequest 'http://127.0.0.1:8000/kiosque' -WebSession $session -UseBasicParsing
if ($r.Content -match '<meta name="csrf-token" content="([^"]+)"') { $token=$matches[1] } else { Write-Output 'NO_TOKEN' ; exit }
Write-Output "TOKEN: $token"
Invoke-WebRequest 'http://127.0.0.1:8000/cart/add' -Method POST -Body @{_token=$token; vinyle_id=1; quantite=1; fond='standard'} -WebSession $session -UseBasicParsing -ErrorAction SilentlyContinue
$r2 = Invoke-WebRequest 'http://127.0.0.1:8000/login' -WebSession $session -UseBasicParsing
if ($r2.Content -match '<meta name="csrf-token" content="([^"]+)"') { $token2=$matches[1] } else { $token2=$token }
Write-Output "LOGIN TOKEN: $token2"
$login = Invoke-WebRequest 'http://127.0.0.1:8000/login' -Method POST -Body @{_token=$token2; email='merge_test@example.com'; password='password'} -WebSession $session -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue
Write-Output "LOGIN STATUS: $($login.StatusCode)"
$c = Invoke-WebRequest 'http://127.0.0.1:8000/cart' -WebSession $session -UseBasicParsing -ErrorAction SilentlyContinue
Write-Output "CART STATUS: $($c.StatusCode)"
if ($c.Content -match 'Quantité|Panier|Votre sélection') { Write-Output 'HAS_CART_CONTENT' } else { Write-Output 'NO_CART_CONTENT' }
