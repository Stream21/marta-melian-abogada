import { useRouter } from '@tanstack/react-router';
import { useState } from 'react';
import { loginRequest } from '@/api/client';
import { useAuth } from '@/contexts/AuthContext';
import { Button } from '@/components/ui/button';
import { Loader2 } from 'lucide-react';

export function LoginPage() {
  const { login } = useAuth();
  const router = useRouter();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setIsLoading(true);

    try {
      const { token } = await loginRequest(email, password);
      login(token);
      await router.navigate({ to: '/expedientes' });
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Error al iniciar sesión.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-muted font-sans flex items-center justify-center p-4">
      <section className="w-full max-w-sm lg:max-w-4xl overflow-hidden rounded-2xl shadow-xl border flex flex-col lg:flex-row">

        <div className="bg-primary flex flex-col items-center justify-center py-10 px-8 lg:w-2/5 lg:py-0 lg:px-12 lg:min-h-[520px]">
          <img
            src="/logo.png"
            alt="Bufete Melián"
            className="h-28 w-auto object-contain lg:h-44"
          />
          <div className="mt-6 text-center">
            <div className="h-px w-8 bg-primary-foreground/20 mx-auto mb-4" />
            <p className="text-primary-foreground/50 text-[10px] lg:text-[11px] tracking-[0.25em] uppercase font-medium">
              Servicios Jurídicos Profesionales
            </p>
          </div>
        </div>

        <div className="bg-card px-8 py-10 lg:w-3/5 lg:px-14 lg:py-0 lg:min-h-[520px] flex flex-col justify-center">

          {error && (
            <div className="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {error}
            </div>
          )}

          <form className="space-y-6" onSubmit={handleSubmit}>
            <div className="space-y-2">
              <label
                htmlFor="email"
                className="section-label block"
              >
                Correo Electrónico
              </label>
              <input
                id="email"
                name="email"
                type="email"
                required
                placeholder="ejemplo@abogado.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="input-field px-4 py-3"
              />
            </div>

            <div className="space-y-2">
              <label
                htmlFor="password"
                className="section-label block"
              >
                Contraseña
              </label>
              <input
                id="password"
                name="password"
                type="password"
                required
                placeholder="••••••••"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="input-field px-4 py-3"
              />
            </div>

            <div className="pt-4">
              <Button
                type="submit"
                disabled={isLoading}
                className="w-full py-3.5 px-6 text-xs font-semibold tracking-widest uppercase"
                size="lg"
              >
                {isLoading ? (
                  <span className="flex items-center justify-center gap-2">
                    <Loader2 className="h-4 w-4 animate-spin" />
                    Accediendo…
                  </span>
                ) : (
                  'Acceder'
                )}
              </Button>
            </div>
          </form>
        </div>
      </section>
    </div>
  );
}
