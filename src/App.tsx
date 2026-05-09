/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { Mail, Lock, ArrowRight, CheckCircle2, AlertCircle, ChefHat } from 'lucide-react';

export default function App() {
  const [isLogin, setIsLogin] = useState(true);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState<{ type: 'success' | 'error', text: string } | null>(null);
  const [designOption] = useState<'split'>('split');

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setMessage(null);

    try {
      const response = await fetch('/api/auth', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          email,
          password,
          action: isLogin ? 'login' : 'signup'
        }),
      });

      const data = await response.json();
      if (response.ok) {
        setMessage({ type: 'success', text: data.message });
        if (!isLogin) {
          setTimeout(() => {
            setIsLogin(true);
            setMessage(null);
          }, 2000);
        }
      } else {
        setMessage({ type: 'error', text: data.message });
      }
    } catch (err) {
      setMessage({ type: 'error', text: 'May naganap na error. Subukan muli.' });
    } finally {
      setLoading(false);
    }
  };

  const renderLogo = (size: 'sm' | 'md' | 'lg' = 'md') => {
    const scales = {
      sm: 'h-12',
      md: 'h-24',
      lg: 'h-32'
    };
    
    return (
      <div className="relative group flex items-start">
        <img 
          src="/logo.png" 
          alt="Mang Inasal Logo" 
          className={`${scales[size]} w-auto object-contain transition-transform group-hover:scale-105 duration-300 mix-blend-multiply`}
          onError={(e) => {
            (e.target as HTMLImageElement).style.display = 'none';
            const fallback = document.getElementById('logo-fallback');
            if (fallback) fallback.style.display = 'block';
          }}
        />
        <div 
          id="logo-fallback" 
          style={{ display: 'none' }} 
          className={`inline-block bg-[#ffec00] p-4 border-[4px] border-black rounded-sm shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] transition-transform group-hover:scale-105`}
        >
          <div className="text-black font-['Poppins'] font-black leading-none tracking-tighter">
            <span className="block text-sm uppercase italic text-black">Mang</span>
            <span className="block text-4xl uppercase text-[#ed1c24] -mt-1">Inasal</span>
          </div>
        </div>
      </div>
    );
  };

  return (
    <div className="min-h-screen bg-[#fcfbf7] font-['Outfit'] flex flex-col items-center justify-center p-4 sm:p-8">
      <motion.div 
        key="split"
        initial={{ opacity: 0, scale: 0.95 }}
        animate={{ opacity: 1, scale: 1 }}
        className="w-full max-w-5xl bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row min-h-[600px]"
      >
        {/* Left Side: Brand Panel */}
        <div id="brand-panel" className="w-full md:w-1/2 bg-[#006738] relative p-12 flex flex-col justify-between overflow-hidden">
          <div className="absolute top-[-10%] right-[-10%] w-[120%] h-[120%] opacity-10 pointer-events-none">
             <ChefHat className="w-full h-full rotate-12 text-[#ffec00]" />
          </div>
          <div className="relative z-10">{renderLogo('md')}</div>
          <div className="relative z-10 space-y-6">
             <motion.div initial={{ opacity: 0, x: -20 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: 0.2 }}>
                <h2 className="text-white text-3xl md:text-4xl font-bold leading-tight font-['Poppins']">
                  Ito ang 2-in-1 sa laki, <br />
                  <span className="text-[#ffec00]">Nuot sa ihaw-sarap!</span>
                </h2>
                <p className="text-green-50/80 mt-4 text-lg">Authentic Filipino charcoal-grilled chicken that satisfies every craving.</p>
             </motion.div>
          </div>
          <div className="relative z-10 mt-8">
            <p className="text-xs text-white/40 uppercase tracking-widest font-semibold">© 2026 Mang Inasal</p>
          </div>
        </div>

        {/* Right Side: Form Panel */}
        <div id="form-panel" className="w-full md:w-1/2 p-8 md:p-16 flex flex-col justify-center">
          <AuthForm 
            isLogin={isLogin} setIsLogin={setIsLogin}
            email={email} setEmail={(val) => setEmail(val as string)}
            password={password} setPassword={(val) => setPassword(val as string)}
            loading={loading} message={message}
            handleSubmit={handleSubmit}
          />
        </div>
      </motion.div>
    </div>
  );

}

function AuthForm({ isLogin, setIsLogin, email, setEmail, password, setPassword, loading, message, handleSubmit }: AuthFormProps) {
  return (
    <div className="space-y-6">
      <div className="space-y-1">
        <h3 className="text-2xl font-bold text-slate-800 font-['Poppins']">
          {isLogin ? 'Welcome Back!' : 'Create Account'}
        </h3>
        <p className="text-slate-500 text-sm">
          {isLogin ? 'Masarap na pagkain ang naghihintay sa iyo.' : 'Sumali sa pamilyang Mang Inasal ngayon!'}
        </p>
      </div>

      <AnimatePresence mode="wait">
        {message && (
          <motion.div 
            initial={{ opacity: 0, height: 0 }}
            animate={{ opacity: 1, height: 'auto' }}
            exit={{ opacity: 0, height: 0 }}
            className={`flex items-center gap-3 p-4 rounded-xl border text-sm font-medium ${
              message.type === 'success' 
                ? 'bg-green-50 border-green-200 text-green-700' 
                : 'bg-red-50 border-red-200 text-red-700'
            }`}
          >
            {message.type === 'success' ? <CheckCircle2 className="w-5 h-5 flex-shrink-0" /> : <AlertCircle className="w-5 h-5 flex-shrink-0" />}
            <span className="text-sm">{message.text}</span>
          </motion.div>
        )}
      </AnimatePresence>

      <form onSubmit={handleSubmit} className="space-y-5">
        <div className="space-y-2">
          <label className="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Email Address</label>
          <div className="relative group">
            <Mail className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-[#006738] transition-colors" />
            <input
              type="email" required value={email} onChange={(e) => setEmail(e.target.value)}
              placeholder="kaingInasal@example.com"
              className="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-4 pl-12 pr-4 text-slate-800 transition-all outline-none"
            />
          </div>
        </div>

        <div className="space-y-2">
          <label className="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Password</label>
          <div className="relative group">
            <Lock className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-[#006738] transition-colors" />
            <input
              type="password" required value={password} onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••"
              className="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-4 pl-12 pr-4 text-slate-800 transition-all outline-none"
            />
          </div>
          {isLogin && (
            <div className="flex justify-end mt-1">
              <button type="button" className="text-xs font-bold text-[#006738] hover:underline uppercase tracking-wider">
                Forgot?
              </button>
            </div>
          )}
        </div>

        <button
          type="submit" disabled={loading}
          className={`w-full bg-[#006738] hover:bg-[#004d29] text-white font-black py-4 rounded-2xl shadow-xl shadow-green-900/10 flex items-center justify-center gap-2 group transition-all transform hover:-translate-y-1 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed`}
        >
          {loading ? (
            <div className="w-6 h-6 border-2 border-white/30 border-t-white rounded-full animate-spin" />
          ) : (
            <>
              <span className="uppercase tracking-widest">
                {isLogin ? 'Login to Order' : 'Sign Up Now'}
              </span>
              <ArrowRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
            </>
          )}
        </button>
      </form>

      <div className="pt-2 text-center">
        <p className="text-sm font-medium text-slate-500">
          {isLogin ? "Don't have an account?" : "Already have an account?"}{' '}
          <button
            onClick={() => setIsLogin(!isLogin)}
            className="text-[#006738] font-black hover:underline uppercase tracking-tight"
          >
            {isLogin ? 'Sign Up' : 'Log In'}
          </button>
        </p>
      </div>
    </div>
  );
}

interface AuthFormProps {
  isLogin: boolean;
  setIsLogin: (val: boolean) => void;
  email: string;
  setEmail: (val: boolean) => void;
  password: string;
  setPassword: (val: boolean) => void;
  loading: boolean;
  message: { type: 'success' | 'error', text: string } | null;
  handleSubmit: (e: React.FormEvent) => void;
}

