<template>
    <div>
        <div v-if="showOclusion" id="oclusionForAlert">
            <div id="pwaInstallPrompt">
                <div style="flex: 1;">
                    <p style="margin: 0; font-size: 14px; line-height: 1.5;">
                        Para uma melhor experiência, utilize a versão instalada do aplicativo.
                    </p>
                    <p id="pwaInstallInstruction" style="margin: 8px 0 0; font-size: 12px; color: #666;">
                        (Se ainda não instalou, clique no botão abaixo)
                    </p>
                </div>
                <div class="flex gap-2">
                    <button id="installPWAButton" @click="openInstallPrompt">
                        Instalar App
                    </button>
                    <button id="recuseInstall" @click="recuseInstall">
                        Agora Não
                    </button>
                </div>
            </div>
        </div>
        <div v-if="showGuideIOS" id="guideIOSInstall" >
            <span class="closeInstallPWAButton" @click="closeIOSGuide">X</span>
            <div class="guide-body">
                <h3>Siga os passos abaixo para instalar no IOS</h3>
                <img src="/resources/img/ios-guide.png" alt="Guia de Instalação iOS">
            </div>
        </div>
    </div>
</template>
<script>
    import { checkIsInstall, promptInstall, recusedPrompt, checkIOS, isInstallPromptReady } from '@/utils/installPWAHelper';
    export default {
        name: 'InstallGuideIOS',
        data() {
            return {
                showOclusion: false,
                showGuideIOS: false,
            }
        },
        mounted() {
            if (!checkIsInstall()) {
                this.showOclusion = true;
            }
        },
        methods: {
            openInstallPrompt() {
                if (checkIOS()) {
                    this.showOclusion = false;
                    this.showGuideIOS = true;
                    return;
                }
                
                const success = promptInstall();
                if (success) {
                    this.showOclusion = false;
                } else {
                    // Wait a bit and try again, or show a message
                    setTimeout(() => {
                        const retrySuccess = promptInstall();
                        if (retrySuccess) {
                            this.showOclusion = false;
                        } else {
                            alert('Instalação não disponível no momento. Tente novamente em alguns segundos.');
                        }
                    }, 1000);
                }
            },
            recuseInstall() {
                this.showOclusion = false;
                recusedPrompt();
            },
            closeIOSGuide() {
                this.showGuideIOS = false;
                this.showOclusion = true;
            }
        }
    }
</script>
<style scoped>
    .closeInstallPWAButton {
        position: relative;
        top: -26px;
        left: -10px;
        height: 20px;
        width: 20px;
        font-size: 15px;
        background: #b10d0d;
        border: none;
        border-radius: 50%;
        box-shadow: -4px 3px 6px 0px #000000ad;
    }

    .closeInstallPWAButton:hover {
        background: #910808;
        border-radius: 50%;
        box-shadow: inset -2px 1px 5px 0px #000000ad;
    }

    #oclusionForAlert {
        z-index: 10000;
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 100vw;
        background: #5d5b5b57;
        backdrop-filter: blur(3px);

        & #pwaInstallPrompt {
            position: fixed;
            bottom: 6rem;
            left: 50%;
            transform: translateX(-50%);
            background: #ffffff;
            color: #333333;
            padding: 16px;
            border-radius: 12px;
            flex-direction: column;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 16px;
            font-family: Arial, sans-serif;
            z-index: 1000;
            max-width: 90%;
            width: 400px;
            text-align: center;

            & #installPWAButton,
            & #recuseInstall {
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 8px;
                font-size: 14px;
                cursor: pointer;
                transition: background 0.3s ease;
            }

            & #installPWAButton {
                background: #007bff;
            }

            & #installPWAButton:hover {
                background: #0056b3;
            }

            & #recuseInstall {
                background: #9f9f9f;
            }

            & #recuseInstall:hover {
                background: #7f7f7f;
            }
        }
    }

    #guideIOSInstall {
        position: fixed;
        top: 0;
        left: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        border-radius: 10px;
        width: 100vw;
        height: 100vh;
        background: #5d5b5b57;
        backdrop-filter: blur(3px);
        z-index: 10000;

        & .closeInstallPWAButton {
            height: 1rem;
            width: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 1rem;
            top: 4%;
            left: 40%;
            font-size: 14px;
            border: none;
            background: #b10d0d;
            border-radius: 50%;
            box-shadow: -4px 3px 6px 0px #000000ad;
        }

        & .closeInstallPWAButton:hover {
            background: #910808;
            border-radius: 50%;
            box-shadow: inset -2px 1px 5px 0px #000000ad;
        }

        & .guide-body {
            padding: 1rem;
            margin: 1rem;
            background: #2e3437;
            height: 60%;
            width: 80%;
            overflow-y: auto;
            border-radius: 20px;
            box-shadow: 0px 7px 10px 1px #000000ab;

            & img {
                width: 100%;
            }

            h3 {
                color: white;
                text-align: center;
                font-size: 15px;
            }
        }
    }
</style>