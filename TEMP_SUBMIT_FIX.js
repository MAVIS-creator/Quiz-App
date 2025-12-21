// REPLACE THE submitQuiz RETRY LOOP (lines 1016-1038) WITH THIS:

                // Retry logic for network resilience
                while (retries > 0) {
                    try {
                        // Create abort controller for timeout (compatible approach)
                        const controller = new AbortController();
                        const timeoutId = setTimeout(() => controller.abort(), 30000); // 30s timeout
                        
                        response = await fetch(`${API}/sessions.php`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(submitPayload),
                            signal: controller.signal
                        });
                        
                        clearTimeout(timeoutId); // Clear timeout on success
                        data = await response.json();
                        break; // Success, exit retry loop
                    } catch (err) {
                        lastError = err;
                        retries--;
                        if (retries > 0) {
                            await new Promise(r => setTimeout(r, 1000)); // Wait 1s before retry
                        }
                    }
                }
