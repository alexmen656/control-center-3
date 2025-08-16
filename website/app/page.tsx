'use client'

import { motion } from 'framer-motion'
import { 
  ArrowRight, 
  Code, 
  Database, 
  GitBranch, 
  Globe, 
  Monitor, 
  Rocket, 
  Server,
  Users,
  Zap,
  Cloud,
  BarChart3,
  Smartphone,
  Shield,
  Cpu
} from 'lucide-react'
import Link from 'next/link'

export default function Home() {
  return (
    <div className="min-h-screen bg-dark-950">
      {/* Navigation */}
      <nav className="fixed top-0 w-full z-50 glass">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center py-4">
            <div className="flex items-center space-x-2">
              <div className="w-10 h-10 bg-red-gradient rounded-lg flex items-center justify-center">
                <Monitor className="w-6 h-6 text-white" />
              </div>
              <span className="text-xl font-bold text-white">Control Center</span>
            </div>
            <div className="hidden md:flex items-center space-x-8">
              <a href="#features" className="text-gray-300 hover:text-white transition-colors">Features</a>
              <a href="#apis" className="text-gray-300 hover:text-white transition-colors">APIs</a>
              <a href="#pricing" className="text-gray-300 hover:text-white transition-colors">Pricing</a>
              <Link 
                href="https://docs.control-center.eu" 
                className="text-gray-300 hover:text-white transition-colors"
                target="_blank"
              >
                Documentation
              </Link>
            </div>
            <div className="flex items-center space-x-4">
              <Link 
                href="https://app.control-center.eu/login" 
                className="text-gray-300 hover:text-white transition-colors"
                target="_blank"
              >
                Login
              </Link>
              <Link 
                href="https://app.control-center.eu/signup" 
                className="bg-red-gradient text-white px-6 py-2 rounded-lg hover:glow transition-all duration-300"
                target="_blank"
              >
                Get Started
              </Link>
            </div>
          </div>
        </div>
      </nav>

      {/* Hero Section */}
      <section className="pt-32 pb-20 px-4">
        <div className="max-w-7xl mx-auto text-center">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8 }}
          >
            <h1 className="text-5xl md:text-7xl font-bold text-white mb-6">
              Professional{' '}
              <span className="bg-gradient-to-r from-red-500 to-red-700 bg-clip-text text-transparent">
                CMS
              </span>
              <br />
              for Modern Developers
            </h1>
            <p className="text-xl text-gray-300 mb-8 max-w-3xl mx-auto">
              Build, deploy, and manage applications with integrated codespaces, 
              API management, and powerful development workflows.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Link 
                href="https://app.control-center.eu/signup"
                className="bg-red-gradient text-white px-8 py-4 rounded-lg text-lg font-semibold hover:glow transition-all duration-300 flex items-center justify-center"
                target="_blank"
              >
                Start Building <ArrowRight className="ml-2 w-5 h-5" />
              </Link>
              <Link 
                href="https://docs.control-center.eu"
                className="border border-gray-600 text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-gray-800 transition-all duration-300"
                target="_blank"
              >
                View Documentation
              </Link>
            </div>
          </motion.div>
        </div>
      </section>

      {/* Features Grid */}
      <section id="features" className="py-20 px-4">
        <div className="max-w-7xl mx-auto">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8 }}
            className="text-center mb-16"
          >
            <h2 className="text-4xl font-bold text-white mb-4">Powerful Features</h2>
            <p className="text-xl text-gray-400">Everything you need for modern web development</p>
          </motion.div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {[
              {
                icon: <Code className="w-8 h-8" />,
                title: "Integrated Codespaces",
                description: "Full VS Code environment in your browser with Monaco editor integration"
              },
              {
                icon: <Database className="w-8 h-8" />,
                title: "API Management",
                description: "Subscribe to APIs, manage keys, and track usage with comprehensive analytics"
              },
              {
                icon: <GitBranch className="w-8 h-8" />,
                title: "Git Integration",
                description: "Built-in version control with GitHub integration and automated workflows"
              },
              {
                icon: <Rocket className="w-8 h-8" />,
                title: "One-Click Deploy",
                description: "Deploy to Vercel, Render, and other platforms with automatic CI/CD"
              },
              {
                icon: <Globe className="w-8 h-8" />,
                title: "Web Builder",
                description: "Visual website builder with drag-and-drop components"
              },
              {
                icon: <Server className="w-8 h-8" />,
                title: "Service Management",
                description: "Manage external services and integrations from one dashboard"
              },
              {
                icon: <Users className="w-8 h-8" />,
                title: "User Management",
                description: "Complete authentication system with roles and permissions"
              },
              {
                icon: <BarChart3 className="w-8 h-8" />,
                title: "Analytics Dashboard",
                description: "Real-time project metrics and usage analytics"
              },
              {
                icon: <Shield className="w-8 h-8" />,
                title: "Enterprise Security",
                description: "JWT authentication, API rate limiting, and security monitoring"
              }
            ].map((feature, index) => (
              <motion.div
                key={index}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.8, delay: index * 0.1 }}
                className="glass rounded-xl p-6 hover:glow-hover"
              >
                <div className="text-red-500 mb-4">{feature.icon}</div>
                <h3 className="text-xl font-semibold text-white mb-2">{feature.title}</h3>
                <p className="text-gray-400">{feature.description}</p>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* API Section */}
      <section id="apis" className="py-20 px-4 bg-dark-900">
        <div className="max-w-7xl mx-auto">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8 }}
            className="text-center mb-16"
          >
            <h2 className="text-4xl font-bold text-white mb-4">Comprehensive API Ecosystem</h2>
            <p className="text-xl text-gray-400">Subscribe to APIs and integrate powerful services</p>
          </motion.div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {[
              { icon: <Users className="w-6 h-6" />, name: "User Management", desc: "Authentication & user operations" },
              { icon: <Cloud className="w-6 h-6" />, name: "File Storage", desc: "Upload & manage files" },
              { icon: <Database className="w-6 h-6" />, name: "Database API", desc: "CRUD operations & queries" },
              { icon: <Zap className="w-6 h-6" />, name: "Notifications", desc: "Push notifications & alerts" },
              { icon: <BarChart3 className="w-6 h-6" />, name: "Analytics", desc: "Event tracking & reporting" },
              { icon: <Cpu className="w-6 h-6" />, name: "AI/ML APIs", desc: "OpenAI, Gemini integrations" },
              { icon: <GitBranch className="w-6 h-6" />, name: "GitHub API", desc: "Repository management" },
              { icon: <Smartphone className="w-6 h-6" />, name: "Mobile APIs", desc: "iOS & Android support" }
            ].map((api, index) => (
              <motion.div
                key={index}
                initial={{ opacity: 0, scale: 0.9 }}
                whileInView={{ opacity: 1, scale: 1 }}
                transition={{ duration: 0.5, delay: index * 0.1 }}
                className="glass rounded-lg p-4 text-center hover:border-red-500/50 transition-all duration-300"
              >
                <div className="text-red-500 flex justify-center mb-3">{api.icon}</div>
                <h3 className="text-white font-semibold mb-1">{api.name}</h3>
                <p className="text-gray-400 text-sm">{api.desc}</p>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-20 px-4">
        <div className="max-w-4xl mx-auto text-center">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8 }}
          >
            <h2 className="text-4xl font-bold text-white mb-6">
              Ready to accelerate your development?
            </h2>
            <p className="text-xl text-gray-400 mb-8">
              Join thousands of developers building faster with Control Center CMS
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Link 
                href="https://app.control-center.eu/signup"
                className="bg-red-gradient text-white px-8 py-4 rounded-lg text-lg font-semibold hover:glow transition-all duration-300"
                target="_blank"
              >
                Start Free Trial
              </Link>
              <Link 
                href="https://docs.control-center.eu"
                className="border border-gray-600 text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-gray-800 transition-all duration-300"
                target="_blank"
              >
                Learn More
              </Link>
            </div>
          </motion.div>
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t border-gray-800 py-12 px-4">
        <div className="max-w-7xl mx-auto">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
              <div className="flex items-center space-x-2 mb-4">
                <div className="w-8 h-8 bg-red-gradient rounded flex items-center justify-center">
                  <Monitor className="w-5 h-5 text-white" />
                </div>
                <span className="text-lg font-bold text-white">Control Center</span>
              </div>
              <p className="text-gray-400">
                Professional CMS for modern developers
              </p>
            </div>
            <div>
              <h3 className="text-white font-semibold mb-4">Product</h3>
              <ul className="space-y-2 text-gray-400">
                <li><a href="#features" className="hover:text-white transition-colors">Features</a></li>
                <li><a href="#apis" className="hover:text-white transition-colors">APIs</a></li>
                <li><a href="#pricing" className="hover:text-white transition-colors">Pricing</a></li>
              </ul>
            </div>
            <div>
              <h3 className="text-white font-semibold mb-4">Resources</h3>
              <ul className="space-y-2 text-gray-400">
                <li>
                  <Link href="https://docs.control-center.eu" className="hover:text-white transition-colors" target="_blank">
                    Documentation
                  </Link>
                </li>
                <li><a href="#" className="hover:text-white transition-colors">Blog</a></li>
                <li><a href="#" className="hover:text-white transition-colors">Support</a></li>
              </ul>
            </div>
            <div>
              <h3 className="text-white font-semibold mb-4">Company</h3>
              <ul className="space-y-2 text-gray-400">
                <li><a href="#" className="hover:text-white transition-colors">About</a></li>
                <li><a href="#" className="hover:text-white transition-colors">Contact</a></li>
                <li><a href="#" className="hover:text-white transition-colors">Privacy</a></li>
              </ul>
            </div>
          </div>
          <div className="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
            <p>&copy; 2024 Control Center CMS. All rights reserved.</p>
          </div>
        </div>
      </footer>
    </div>
  )
}
