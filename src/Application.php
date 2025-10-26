<?php

namespace TicketApp;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Application
{
    private $twig;
    private $session;
    private $tickets;

    public function __construct()
    {
        // Initialize Twig
        $loader = new FilesystemLoader(__DIR__ . '/../templates');
        $this->twig = new Environment($loader, [
            'cache' => false, // Disable cache for development
            'debug' => true,
        ]);

        // Initialize session
        $this->session = new Session();
        $this->session->start();

        // Initialize mock ticket data
        $this->initializeTickets();
    }

    private function initializeTickets()
    {
        if (!$this->session->has('tickets')) {
            $this->tickets = [
                [
                    'id' => 1,
                    'title' => 'Login issue with mobile app',
                    'description' => 'Users are unable to log in using the mobile application. The error occurs after entering credentials.',
                    'status' => 'open',
                    'priority' => 'high',
                    'created_at' => '2024-01-15 10:30:00',
                    'updated_at' => '2024-01-15 14:20:00',
                    'assignee' => 'John Doe'
                ],
                [
                    'id' => 2,
                    'title' => 'Feature request: Dark mode',
                    'description' => 'Add dark mode support to improve user experience during night time usage.',
                    'status' => 'in_progress',
                    'priority' => 'medium',
                    'created_at' => '2024-01-14 09:15:00',
                    'updated_at' => '2024-01-15 11:45:00',
                    'assignee' => 'Jane Smith'
                ],
                [
                    'id' => 3,
                    'title' => 'Payment processing error',
                    'description' => 'Payment gateway returns error 500 when processing credit card transactions.',
                    'status' => 'open',
                    'priority' => 'high',
                    'created_at' => '2024-01-14 16:20:00',
                    'updated_at' => '2024-01-14 16:20:00',
                    'assignee' => 'Mike Johnson'
                ],
                [
                    'id' => 4,
                    'title' => 'UI improvement suggestions',
                    'description' => 'Several UI elements could be improved for better accessibility and user experience.',
                    'status' => 'closed',
                    'priority' => 'low',
                    'created_at' => '2024-01-13 14:10:00',
                    'updated_at' => '2024-01-14 10:30:00',
                    'assignee' => 'Sarah Wilson'
                ],
                [
                    'id' => 5,
                    'title' => 'Database performance issue',
                    'description' => 'Queries are running slowly during peak hours, affecting application performance.',
                    'status' => 'in_progress',
                    'priority' => 'high',
                    'created_at' => '2024-01-12 11:00:00',
                    'updated_at' => '2024-01-15 09:15:00',
                    'assignee' => 'Alex Brown'
                ]
            ];
            $this->session->set('tickets', $this->tickets);
        } else {
            $this->tickets = $this->session->get('tickets');
        }
    }

    public function handleRequest(Request $request)
    {
        $path = $request->getPathInfo();
        $method = $request->getMethod();

        // Route handling
        switch ($path) {
            case '/':
                return $this->landingPage();
            
            case '/auth/login':
                return $method === 'POST' ? $this->handleLogin($request) : $this->loginPage();
            
            case '/auth/signup':
                return $method === 'POST' ? $this->handleSignup($request) : $this->signupPage();
            
            case '/auth/logout':
                return $this->handleLogout();
            
            case '/app':
            case '/app/dashboard':
                return $this->requireAuth() ?: $this->dashboardPage();
            
            case '/app/tickets':
                return $this->requireAuth() ?: $this->ticketsPage($request);
            
            case '/app/tickets/create':
                return $this->requireAuth() ?: ($method === 'POST' ? $this->handleCreateTicket($request) : $this->createTicketPage());
            
            default:
                if (preg_match('/^\/app\/tickets\/(\d+)\/edit$/', $path, $matches)) {
                    $ticketId = (int)$matches[1];
                    return $this->requireAuth() ?: ($method === 'POST' ? $this->handleEditTicket($request, $ticketId) : $this->editTicketPage($ticketId));
                }
                
                if (preg_match('/^\/app\/tickets\/(\d+)\/delete$/', $path, $matches)) {
                    $ticketId = (int)$matches[1];
                    return $this->requireAuth() ?: $this->handleDeleteTicket($ticketId);
                }
                
                return $this->notFoundPage();
        }
    }

    private function requireAuth()
    {
        if (!$this->session->has('user')) {
            return new RedirectResponse('/auth/login');
        }
        return null;
    }

    private function landingPage()
    {
        return new Response($this->twig->render('landing.twig'));
    }

    private function loginPage($errors = [], $message = '')
    {
        return new Response($this->twig->render('auth/login.twig', [
            'errors' => $errors,
            'message' => $message
        ]));
    }

    private function signupPage($errors = [], $message = '')
    {
        return new Response($this->twig->render('auth/signup.twig', [
            'errors' => $errors,
            'message' => $message
        ]));
    }

    private function handleLogin(Request $request)
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        
        $errors = $this->validateLogin($email, $password);
        
        if (empty($errors)) {
            // Mock authentication - in real app, verify against database
            $user = [
                'id' => 1,
                'email' => $email,
                'name' => explode('@', $email)[0]
            ];
            
            $this->session->set('user', $user);
            return new RedirectResponse('/app');
        }
        
        return $this->loginPage($errors);
    }

    private function handleSignup(Request $request)
    {
        $name = $request->request->get('name');
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $confirmPassword = $request->request->get('confirm_password');
        
        $errors = $this->validateSignup($name, $email, $password, $confirmPassword);
        
        if (empty($errors)) {
            // Mock user creation
            $user = [
                'id' => 1,
                'email' => $email,
                'name' => $name
            ];
            
            $this->session->set('user', $user);
            return new RedirectResponse('/app');
        }
        
        return $this->signupPage($errors);
    }

    private function handleLogout()
    {
        $this->session->clear();
        return new RedirectResponse('/');
    }

    private function dashboardPage()
    {
        $user = $this->session->get('user');
        $tickets = $this->session->get('tickets', []);
        
        $stats = [
            'total' => count($tickets),
            'open' => count(array_filter($tickets, fn($t) => $t['status'] === 'open')),
            'in_progress' => count(array_filter($tickets, fn($t) => $t['status'] === 'in_progress')),
            'closed' => count(array_filter($tickets, fn($t) => $t['status'] === 'closed'))
        ];
        
        return new Response($this->twig->render('dashboard.twig', [
            'user' => $user,
            'stats' => $stats,
            'recent_tickets' => array_slice($tickets, 0, 5)
        ]));
    }

    private function ticketsPage(Request $request)
    {
        $user = $this->session->get('user');
        $tickets = $this->session->get('tickets', []);
        
        // Handle search and filters
        $search = $request->query->get('search', '');
        $statusFilter = $request->query->get('status', 'all');
        $priorityFilter = $request->query->get('priority', 'all');
        
        if ($search || $statusFilter !== 'all' || $priorityFilter !== 'all') {
            $tickets = array_filter($tickets, function($ticket) use ($search, $statusFilter, $priorityFilter) {
                $matchesSearch = empty($search) || 
                    stripos($ticket['title'], $search) !== false || 
                    stripos($ticket['description'], $search) !== false;
                
                $matchesStatus = $statusFilter === 'all' || $ticket['status'] === $statusFilter;
                $matchesPriority = $priorityFilter === 'all' || $ticket['priority'] === $priorityFilter;
                
                return $matchesSearch && $matchesStatus && $matchesPriority;
            });
        }
        
        return new Response($this->twig->render('tickets/list.twig', [
            'user' => $user,
            'tickets' => $tickets,
            'search' => $search,
            'status_filter' => $statusFilter,
            'priority_filter' => $priorityFilter
        ]));
    }

    private function createTicketPage($errors = [], $data = [])
    {
        $user = $this->session->get('user');
        return new Response($this->twig->render('tickets/create.twig', [
            'user' => $user,
            'errors' => $errors,
            'data' => $data
        ]));
    }

    private function editTicketPage($ticketId, $errors = [])
    {
        $user = $this->session->get('user');
        $tickets = $this->session->get('tickets', []);
        
        $ticket = null;
        foreach ($tickets as $t) {
            if ($t['id'] == $ticketId) {
                $ticket = $t;
                break;
            }
        }
        
        if (!$ticket) {
            return $this->notFoundPage();
        }
        
        return new Response($this->twig->render('tickets/edit.twig', [
            'user' => $user,
            'ticket' => $ticket,
            'errors' => $errors
        ]));
    }

    private function handleCreateTicket(Request $request)
    {
        $data = [
            'title' => $request->request->get('title'),
            'description' => $request->request->get('description'),
            'status' => $request->request->get('status'),
            'priority' => $request->request->get('priority')
        ];
        
        $errors = $this->validateTicket($data);
        
        if (empty($errors)) {
            $tickets = $this->session->get('tickets', []);
            $newId = max(array_column($tickets, 'id')) + 1;
            
            $newTicket = [
                'id' => $newId,
                'title' => trim($data['title']),
                'description' => $data['description'],
                'status' => $data['status'],
                'priority' => $data['priority'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'assignee' => 'Current User'
            ];
            
            $tickets[] = $newTicket;
            $this->session->set('tickets', $tickets);
            
            $this->session->getFlashBag()->add('success', 'Ticket created successfully!');
            return new RedirectResponse('/app/tickets');
        }
        
        return $this->createTicketPage($errors, $data);
    }

    private function handleEditTicket(Request $request, $ticketId)
    {
        $tickets = $this->session->get('tickets', []);
        $ticketIndex = null;
        
        foreach ($tickets as $index => $ticket) {
            if ($ticket['id'] == $ticketId) {
                $ticketIndex = $index;
                break;
            }
        }
        
        if ($ticketIndex === null) {
            return $this->notFoundPage();
        }
        
        $data = [
            'title' => $request->request->get('title'),
            'description' => $request->request->get('description'),
            'status' => $request->request->get('status'),
            'priority' => $request->request->get('priority')
        ];
        
        $errors = $this->validateTicket($data);
        
        if (empty($errors)) {
            $tickets[$ticketIndex]['title'] = trim($data['title']);
            $tickets[$ticketIndex]['description'] = $data['description'];
            $tickets[$ticketIndex]['status'] = $data['status'];
            $tickets[$ticketIndex]['priority'] = $data['priority'];
            $tickets[$ticketIndex]['updated_at'] = date('Y-m-d H:i:s');
            
            $this->session->set('tickets', $tickets);
            
            $this->session->getFlashBag()->add('success', 'Ticket updated successfully!');
            return new RedirectResponse('/app/tickets');
        }
        
        return $this->editTicketPage($ticketId, $errors);
    }

    private function handleDeleteTicket($ticketId)
    {
        $tickets = $this->session->get('tickets', []);
        
        $tickets = array_filter($tickets, fn($ticket) => $ticket['id'] != $ticketId);
        $this->session->set('tickets', array_values($tickets));
        
        $this->session->getFlashBag()->add('success', 'Ticket deleted successfully!');
        return new RedirectResponse('/app/tickets');
    }

    private function notFoundPage()
    {
        return new Response($this->twig->render('404.twig'), 404);
    }

    private function validateLogin($email, $password)
    {
        $errors = [];
        
        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email is invalid';
        }
        
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }
        
        return $errors;
    }

    private function validateSignup($name, $email, $password, $confirmPassword)
    {
        $errors = [];
        
        if (empty($name)) {
            $errors['name'] = 'Name is required';
        } elseif (strlen($name) < 2) {
            $errors['name'] = 'Name must be at least 2 characters';
        }
        
        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email is invalid';
        }
        
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }
        
        if (empty($confirmPassword)) {
            $errors['confirm_password'] = 'Please confirm your password';
        } elseif ($password !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
        
        return $errors;
    }

    private function validateTicket($data)
    {
        $errors = [];
        
        // Title validation (required)
        if (empty(trim($data['title']))) {
            $errors['title'] = 'Title is required';
        } elseif (strlen(trim($data['title'])) < 3) {
            $errors['title'] = 'Title must be at least 3 characters long';
        } elseif (strlen(trim($data['title'])) > 100) {
            $errors['title'] = 'Title must be less than 100 characters';
        }
        
        // Description validation (optional but with length limits)
        if (!empty($data['description']) && strlen($data['description']) > 1000) {
            $errors['description'] = 'Description must be less than 1000 characters';
        }
        
        // Status validation (required, must be one of allowed values)
        $allowedStatuses = ['open', 'in_progress', 'closed'];
        if (empty($data['status'])) {
            $errors['status'] = 'Status is required';
        } elseif (!in_array($data['status'], $allowedStatuses)) {
            $errors['status'] = 'Status must be one of: open, in_progress, closed';
        }
        
        // Priority validation (optional but must be valid if provided)
        $allowedPriorities = ['low', 'medium', 'high'];
        if (!empty($data['priority']) && !in_array($data['priority'], $allowedPriorities)) {
            $errors['priority'] = 'Priority must be one of: low, medium, high';
        }
        
        return $errors;
    }
}
