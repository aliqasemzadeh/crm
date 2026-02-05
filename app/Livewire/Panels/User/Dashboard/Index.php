<?php

namespace App\Livewire\Panels\User\Dashboard;

use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    #[\Livewire\Attributes\Computed]
    public function columns()
    {
        return [
            [
                'title' => 'Backlog',
                'cards' => [
                    [
                        'title' => 'User Reports Slow Load Times on Profile Page',
                        'badges' => [
                            ['title' => 'Bug', 'color' => 'red'],
                        ]
                    ],
                    [
                        'title' => 'Inconsistent Button Styles on Settings Page',
                        'badges' => [
                            ['title' => 'UI', 'color' => 'blue'],
                        ]
                    ],
                    [
                        'title' => 'Investigate Unhandled Exception on Login',
                        'badges' => [
                            ['title' => 'Bug', 'color' => 'red'],
                            ['title' => 'High priority', 'color' => 'yellow'],
                        ]
                    ],
                    [
                        'title' => 'Database Migration for New Analytics Table',
                        'badges' => [
                            ['title' => 'Backend', 'color' => 'green'],
                        ]
                    ],
                    [
                        'title' => 'Correct Misalignment of Icons in Footer',
                        'badges' => [
                            ['title' => 'UI', 'color' => 'blue'],
                        ]
                    ]
                ]
            ],

            [
                'title' => 'Planned',
                'cards' => [
                    [
                        'title' => 'Update Privacy Policy in App',
                        'badges' => [
                            ['title' => 'UI', 'color' => 'blue'],
                        ]
                    ],
                    [
                        'title' => 'Fix Issue with Search Bar Auto-Suggestions',
                        'badges' => [
                            ['title' => 'Bug', 'color' => 'red'],
                            ['title' => 'UI', 'color' => 'blue'],
                        ]
                    ],
                    [
                        'title' => 'Improve Loading Spinner Visuals',
                        'badges' => [
                            ['title' => 'UI', 'color' => 'blue'],
                        ]
                    ],
                    [
                        'title' => 'Fix Date Picker Not Accepting Keyboard Input',
                        'badges' => [
                            ['title' => 'Bug', 'color' => 'red'],
                        ]
                    ],
                    [
                        'title' => 'Fix Permissions Issue in Admin Panel',
                        'badges' => [
                            ['title' => 'Backend', 'color' => 'green'],
                            ['title' => 'Bug', 'color' => 'red'],
                        ]
                    ],
                    [
                        'title' => 'Resolve Broken Image Links in Product Gallery',
                        'badges' => [
                            ['title' => 'Bug', 'color' => 'red'],
                        ]
                    ]
                ]
            ],

            [
                'title' => 'In Progress',
                'cards' => [
                    [
                        'title' => 'Responsive Improvements on Mobile',
                        'badges' => [
                            ['title' => 'UI', 'color' => 'blue'],
                        ]
                    ],
                    [
                        'title' => 'Fix Issue with Sorting in Data Tables',
                        'badges' => [
                            ['title' => 'Bug', 'color' => 'red'],
                            ['title' => 'UI', 'color' => 'blue'],
                        ]
                    ],
                    [
                        'title' => 'Update API to Return Consistent Error Codes',
                        'badges' => [
                            ['title' => 'Backend', 'color' => 'green'],
                        ]
                    ],
                    [
                        'title' => 'Accessibility Audit',
                        'badges' => [
                            ['title' => 'UI', 'color' => 'blue'],
                        ]
                    ],
                    [
                        'title' => 'UI/UX Exploration for User Dashboard',
                        'badges' => [
                            ['title' => 'UI', 'color' => 'blue'],
                        ]
                    ]
                ]
            ],

            [
                'title' => 'In review',
                'cards' => [
                    [
                        'title' => 'Resolve Issue with Double-Click on Buttons',
                        'badges' => [
                            ['title' => 'Bug', 'color' => 'red'],
                            ['title' => 'UI', 'color' => 'blue'],
                        ]
                    ],
                    [
                        'title' => 'Crash on Large File Upload',
                        'badges' => [
                            ['title' => 'High priority', 'color' => 'yellow'],
                        ]
                    ],
                    [
                        'title' => 'Concurrent Request Handling in API',
                        'badges' => [
                            ['title' => 'Backend', 'color' => 'green'],
                        ]
                    ]
                ]
            ]
        ];
    }

    #[Layout('layouts.panels.user')]
    public function render()
    {
        return view('livewire.panels.user.dashboard.index');
    }
}
