import { toastController } from '@ionic/vue';

export const ToastService = {
  success(message: string, duration: number = 3000) {
    this.showToast(message, 'success', duration);
  },
  
  error(message: string, duration: number = 3000) {
    this.showToast(message, 'danger', duration);
  },
  
  warning(message: string, duration: number = 3000) {
    this.showToast(message, 'warning', duration);
  },
  
  info(message: string, duration: number = 3000) {
    this.showToast(message, 'medium', duration);
  },
  
  async showToast(message: string, color: string, duration: number) {
    const toast = await toastController.create({
      message: message,
      duration: duration,
      position: 'top',
      color: color,
      cssClass: 'toast-message'
    });
    
    await toast.present();
  }
};