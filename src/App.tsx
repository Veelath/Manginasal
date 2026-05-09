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

  return (
    <div className="min-h-screen bg-[#fcfbf7] font-['Outfit'] flex items-center justify-center p-4 sm:p-8">
      <div id="main-card" className="w-full max-w-5xl bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row min-h-[600px]">
        
        {/* Left Side: Brand Panel */}
        <div id="brand-panel" className="w-full md:w-1/2 bg-[#006738] relative p-12 flex flex-col justify-between overflow-hidden">
          {/* Abstract background elements */}
          <div className="absolute top-[-10%] right-[-10%] w-[120%] h-[120%] opacity-10 pointer-events-none">
             <ChefHat className="w-full h-full rotate-12 text-[#ffec00]" />
          </div>

          <div className="relative z-10">
            <motion.div 
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              className="inline-block bg-[#ffec00] p-4 border-4 border-black rounded-xl shadow-[6px_6px_0px_0px_rgba(0,0,0,1)]"
            >
              <div className="text-black font-['Poppins'] font-black leading-none tracking-tighter">
                <span className="block text-xl uppercase italic">Mang</span>
                <span className="block text-5xl uppercase text-[#ed1c24] -mt-1">Inasal</span>
              </div>
            </motion.div>
          </div>

          <div className="relative z-10 space-y-6">
             <motion.div
               initial={{ opacity: 0, x: -20 }}
               animate={{ opacity: 1, x: 0 }}
               transition={{ delay: 0.2 }}
             >
                <h2 className="text-white text-3xl md:text-4xl font-bold leading-tight">
                  Ito ang 2-in-1 sa laki, <br />
                  <span className="text-[#ffec00]">Nuot sa ihaw-sarap!</span>
                </h2>
                <p className="text-green-50/80 mt-4 text-lg">
                  Authentic Filipino charcoal-grilled chicken that satisfies every craving. Login to order your favorites!
                </p>
             </motion.div>

             <div className="flex gap-4 pt-4">
                {[1, 2, 3].map((i) => (
                  <div key={i} className="w-12 h-1 bg-[#ffec00]/30 rounded-full overflow-hidden">
                    {i === 1 && <motion.div className="h-full bg-[#ffec00]" initial={{ width: 0 }} animate={{ width: '100%' }} transition={{ duration: 1 }} />}
                  </div>
                ))}
             </div>
          </div>

          <div className="relative z-10 mt-8">
            <p className="text-xs text-white/40 uppercase tracking-widest font-semibold">
              © 2026 Mang Inasal Philippines Inc.
            </p>
          </div>
        </div>

        {/* Right Side: Form Panel */}
        <div id="form-panel" className="w-full md:w-1/2 p-8 md:p-16 flex flex-col justify-center">
          <AnimatePresence mode="wait">
            <motion.div
              key={isLogin ? 'login' : 'signup'}
              initial={{ opacity: 0, x: 20 }}
              animate={{ opacity: 1, x: 0 }}
              exit={{ opacity: 0, x: -20 }}
              transition={{ duration: 0.3 }}
              className="space-y-8"
            >
              <div>
                <h3 className="text-3xl font-bold text-slate-800">
                  {isLogin ? 'Welcome Back!' : 'Create Account'}
                </h3>
                <p className="text-slate-500 mt-2">
                  {isLogin 
                    ? 'Masarap na pagkain ang naghihintay sa iyo.' 
                    : 'Sumali sa pamilyang Mang Inasal ngayon!'}
                </p>
              </div>

              {message && (
                <motion.div 
                  initial={{ opacity: 0, height: 0 }}
                  animate={{ opacity: 1, height: 'auto' }}
                  className={`flex items-center gap-3 p-4 rounded-xl border ${
                    message.type === 'success' 
                      ? 'bg-green-50 border-green-200 text-green-700' 
                      : 'bg-red-50 border-red-200 text-red-700'
                  }`}
                >
                  {message.type === 'success' ? <CheckCircle2 className="w-5 h-5" /> : <AlertCircle className="w-5 h-5" />}
                  <span className="text-sm font-medium">{message.text}</span>
                </motion.div>
              )}

              <form onSubmit={handleSubmit} className="space-y-5">
                <div className="space-y-2">
                  <label className="text-sm font-semibold text-slate-700 ml-1">Email Address</label>
                  <div className="relative group">
                    <Mail className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-[#006738] transition-colors" />
                    <input
                      type="email"
                      required
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      placeholder="kaingInasal@example.com"
                      className="w-full bg-[#f1f5f1] border-none rounded-2xl py-4 pl-12 pr-4 text-slate-800 focus:ring-2 focus:ring-[#006738] transition-all outline-none"
                    />
                  </div>
                </div>

                <div className="space-y-2">
                  <label className="text-sm font-semibold text-slate-700 ml-1">Password</label>
                  <div className="relative group">
                    <Lock className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-[#006738] transition-colors" />
                    <input
                      type="password"
                      required
                      value={password}
                      onChange={(e) => setPassword(e.target.value)}
                      placeholder="••••••••"
                      className="w-full bg-[#f1f5f1] border-none rounded-2xl py-4 pl-12 pr-4 text-slate-800 focus:ring-2 focus:ring-[#006738] transition-all outline-none"
                    />
                  </div>
                  {isLogin && (
                    <div className="flex justify-end mt-1">
                      <button type="button" className="text-sm font-semibold text-[#006738] hover:underline">
                        Forgot Password?
                      </button>
                    </div>
                  )}
                </div>

                <button
                  id="submit-button"
                  type="submit"
                  disabled={loading}
                  className={`w-full bg-[#006738] hover:bg-[#004d29] text-white font-bold py-4 rounded-2xl shadow-xl shadow-green-900/10 flex items-center justify-center gap-2 group transition-all transform hover:-translate-y-1 active:translate-y-0 ${loading ? 'opacity-70 cursor-not-allowed' : ''}`}
                >
                  {loading ? (
                    <div className="w-6 h-6 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                  ) : (
                    <>
                      <span className="uppercase tracking-wider">
                        {isLogin ? 'Login to Order' : 'Start Your Journey'}
                      </span>
                      <ArrowRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                    </>
                  )}
                </button>
              </form>

              <div className="pt-4 text-center">
                <p className="text-slate-500">
                  {isLogin ? "Don't have an account?" : "Already have an account?"}{' '}
                  <button
                    onClick={() => setIsLogin(!isLogin)}
                    className="text-[#006738] font-bold hover:underline"
                  >
                    {isLogin ? 'Sign Up' : 'Log In'}
                  </button>
                </p>
              </div>
            </motion.div>
          </AnimatePresence>
        </div>
      </div>
    </div>
  );
}

